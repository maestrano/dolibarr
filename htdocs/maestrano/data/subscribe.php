<?php

require_once '../init.php';

try {
  if(!Maestrano::param('connec.enabled')) { return false; }
  
  // require_once MAESTRANO_ROOT . '/connec/init.php';

  $client = new Maestrano_Connec_Client();

  $notification = json_decode(file_get_contents('php://input'), false);
  $entity_name = strtoupper(trim($notification->entity));
  $entity_id = $notification->id;

  error_log("Received notification = ". json_encode($notification));

  // switch ($entity_name) {
  //   case "COMPANYS":
  //     $companyMapper = new CompanyMapper();
  //     $companyMapper->fetchConnecResource($entity_id);
  //     break;
  //   case "TAXCODES":
  //     $taxMapper = new TaxMapper();
  //     $taxMapper->fetchConnecResource($entity_id);
  //     break;
  //   case "ACCOUNTS":
  //     $accountMapper = new AccountMapper();
  //     $accountMapper->fetchConnecResource($entity_id);
  //     break;
  //   case "ORGANIZATIONS":
  //     $organizationMapper = new CustomerOrganizationMapper();
  //     $organizationMapper->fetchConnecResource($entity_id);
  //     $organizationMapper = new SupplierOrganizationMapper();
  //     $organizationMapper->fetchConnecResource($entity_id);
  //     break;
  //   case "PERSONS":
  //     $contactMapper = new ContactMapper();
  //     $contactMapper->fetchConnecResource($entity_id);
  //     $leadMapper = new LeadMapper();
  //     $leadMapper->fetchConnecResource($entity_id);
  //     break;
  //   case "ITEMS":
  //     $productMapper = new ProductMapper();
  //     $productMapper->fetchConnecResource($entity_id);
  //     $serviceMapper = new ServiceMapper();
  //     $serviceMapper->fetchConnecResource($entity_id);
  //     break;
  //   case "INVOICES":
  //     $invoiceMapper = new InvoiceMapper();
  //     $invoiceMapper->fetchConnecResource($entity_id);
  //     $purchaseOrderMapper = new PurchaseOrderMapper();
  //     $purchaseOrderMapper->fetchConnecResource($entity_id);
  //     break;
  //   case "QUOTES":
  //     $quoteMapper = new QuoteMapper();
  //     $quoteMapper->fetchConnecResource($entity_id);
  //     break;
  //   case "PURCHASEORDERS":
  //     $purchaseOrderMapper = new PurchaseOrderMapper();
  //     $purchaseOrderMapper->fetchConnecResource($entity_id);
  //     break;
  // }
} catch (Exception $e) {
  error_log("Caught exception in subscribe " . json_encode($e->getMessage()));
}
