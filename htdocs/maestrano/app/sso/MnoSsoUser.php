<?php

/**
 * Configure App specific behavior for Maestrano SSO
 */
class MnoSsoUser extends Maestrano_Sso_User {
  public $connection = null;
  public $_user = null;
  public $_db_tbl_prefix = 'llx_';

  /**
   * Construct the Maestrano_Sso_User object from a SAML response
   *
   * @param Maestrano_Saml_Response $saml_response
   *   A SamlResponse object from Maestrano containing details
   *   about the user being authenticated
   */
  public function __construct($saml_response) {
    parent::__construct($saml_response);

    global $opts;
    $this->connection = $opts['db_connection'];
    $this->_app_conf = $opts['app_conf'];
    
    if ($opts['db_table_prefix']) {
      $this->_db_tbl_prefix = $opts['db_table_prefix'];
    }
    
    // Assign global variable $conf (used internally by dolibarr)
    $conf = $opts['app_conf'];
  }

  /**
  * Find or Create a user based on the SAML response parameter and Add the user to current session
  */
  public function findOrCreate() {
    // Find user by uid or email
    $local_id = $this->getLocalIdByUid();
    if($local_id == null) { $local_id = $this->getLocalIdByEmail(); }

    if ($local_id) {
      // User found, load it
      $this->local_id = $local_id;
      $this->syncLocalDetails();
    } else {
      // New user, create it
      $this->local_id = $this->createLocalUser();
      $this->setLocalUid();
    }

    // Add user to current session
    $this->setInSession();
  }
  
  /**
   * Sign the user in the application. 
   * Parent method deals with putting the mno_uid, 
   * mno_session and mno_session_recheck in session.
   *
   * @return boolean whether the user was successfully set in session or not
   */
  protected function setInSession() {
    // First set $conn variable (need global variable?)
    $_SESSION["dol_login"] = $this->uid;
    $_SESSION["dol_authmode"]='';
    $_SESSION["dol_tz"]='';
    $_SESSION["dol_tz_string"]='';
    $_SESSION["dol_dst"]='';
    $_SESSION["dol_dst_observed"]='';
    $_SESSION["dol_dst_first"]='';
    $_SESSION["dol_dst_second"]='';
    $_SESSION["dol_screenwidth"]='';
    $_SESSION["dol_screenheight"]='';
    $_SESSION["dol_company"]='';
    $_SESSION["dol_entity"]=1;
    
    // Used by extensions like cashdesk
    $_SESSION['uid'] = $this->local_id;
    $_SESSION['uname'] = $this->local_id;
    $_SESSION['lastname'] = $this->getLastName();
    $_SESSION['firstname'] = $this->getFirstName();
    
    return true;
  }
  
  
  /**
   * Used by createLocalUserOrDenyAccess to create a local user 
   * based on the sso user.
   * If the method returns null then access is denied
   *
   * @return the ID of the user created, null otherwise
   */
  protected function createLocalUser() {
    $lid = null;
    
    // Build the local user first
    $this->buildLocalUser();
    
    // Save the user and capture the id
    $this->connection->begin();
    $lid = $this->_user->create($this->_user);
    if ($lid > 0) {
      $this->_user->setPassword($this->_user, $this->generatePassword());
      if($this->_user->admin) { $this->assignAllRights(); }
    }
    $this->connection->commit();
    
    return $lid;
  }
  
  /**
   * Build a local user for creation
   */
  protected function buildLocalUser() {
    $u = new User($this->connection);
    $u->lastname     = $this->getLastName();
    $u->firstname    = $this->getFirstName();
    $u->login        = $this->uid;
    $u->admin        = $this->isAdmin();
    $u->office_phone = "";
    $u->office_fax   = "";
    $u->user_mobile  = "";
    $u->email        = $this->getEmail();
    $u->job          = "";
    $u->signature    = "";
    $u->note         = "";
    $u->ldap_sid     = "";
    $u->entity       = 1;

    $this->_user = $u;

    return $this->_user;
  }
  
  protected function assignAllRights() {
    // Delete all assigned rights
    $sql = "DELETE FROM ".MAIN_DB_PREFIX."user_rights WHERE fk_user = " . $this->_user->id;
    $result = $this->connection->query($sql);

    // Assign all available rights
    $sql = "SELECT id FROM ".MAIN_DB_PREFIX."rights_def";
    $resql = $this->connection->query($sql);
    if($resql) {
      $num = $this->connection->num_rows($resql);
      $i = 0;
      while ($i < $num) {
        $row = $this->connection->fetch_row($resql);
        $sql = "INSERT INTO ".MAIN_DB_PREFIX."user_rights (fk_user, fk_id) VALUES (".$this->_user->id.", ".$row[0].")";
        $result = $this->connection->query($sql);

        $i++;
      }
      $this->connection->free($resql);
    }
  }
  
  /**
   * Check wether a user should be set admin in
   * dolibarr or not
   *
   * @return boolean true if admin false otherwise
   */
  protected function isAdmin()
  {
    switch($this->getGroupRole()) {
      case 'Member':
        return false;
      case 'Power User':
        return false;
      case 'Admin':
        return true;
      case 'Super Admin':
        return true;
      default:
        return false;
    }
  }
  
  /**
   * Get the ID of a local user via Maestrano UID lookup
   *
   * @return a user ID if found, null otherwise
   */
  protected function getLocalIdByUid() {
    $sql = "SELECT rowid FROM " . $this->_db_tbl_prefix . "user WHERE mno_uid = '{$this->connection->escape($this->uid)}' LIMIT 1";
    $result = $this->connection->query($sql);
    if ($result) $result = $this->connection->fetch_object($result);
    
    if ($result && $result->rowid) {
      return $result->rowid;
    }
    
    return null;
  }
  
  /**
   * Get the ID of a local user via email lookup
   *
   * @return a user ID if found, null otherwise
   */
  protected function getLocalIdByEmail() {
    $sql = "SELECT rowid FROM " . $this->_db_tbl_prefix . "user WHERE email = '{$this->connection->escape($this->getEmail())}' LIMIT 1";
    $result = $this->connection->query($sql);
    if ($result) $result = $this->connection->fetch_object($result);
    
    if ($result && $result->rowid) {
      return $result->rowid;
    }
    
    return null;
  }
  
  /**
   * Set all 'soft' details on the user (like name, surname, email)
   * Implementing this method is optional.
   *
   * @return boolean whether the user was synced or not
   */
   protected function syncLocalDetails() {
     if($this->local_id) {
       
       // Prepare sql statement
       $sql = "UPDATE " . $this->_db_tbl_prefix . "user SET
         login = '{$this->connection->escape($this->uid)}',
         firstname = '{$this->connection->escape($this->getFirstName())}',
         lastname = '{$this->connection->escape($this->getLastName())}',
         email = '{$this->connection->escape($this->getEmail())}'
         WHERE rowid = $this->local_id";
       
       // Execute
       $this->connection->begin();
       $upd = $this->connection->query($sql);
       $this->connection->commit();
       
       return $upd;
     }
     
     return false;
   }
  
  /**
   * Set the Maestrano UID on a local user via id lookup
   *
   * @return a user ID if found, null otherwise
   */
  protected function setLocalUid() {
    if($this->local_id) {
      // Prepare statement
      $sql = "UPDATE " . $this->_db_tbl_prefix . "user SET mno_uid = '{$this->connection->escape($this->uid)}' WHERE rowid = $this->local_id";
      
      // Execute
      $this->connection->begin();
      $upd = $this->connection->query($sql);
      $this->connection->commit();
      
      return $upd;
    }
    
    return false;
  }

   /**
  * Generate a random password.
  * Convenient to set dummy passwords on users
  *
  * @return string a random password
  */
  protected function generatePassword() {
    $length = 20;
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
      $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
  }
}