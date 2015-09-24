<?php

require_once '../init.php';

try {
  if(!Maestrano::param('connec.enabled')) { return false; }
  
  require_once MAESTRANO_ROOT . '/connec/init.php';

  $client = new Maestrano_Connec_Client();

  $notification = json_decode(file_get_contents('php://input'), false);
  $entity_name = strtoupper(trim($notification->entity));
  $entity_id = $notification->id;

  error_log("Received notification = ". json_encode($notification));

  switch ($entity_name) {
    case "COMPANYS":
      $companyMapper = new CompanyMapper();
      $companyMapper->fetchConnecResource($entity_id);
      break;
    case "TAXCODES":
      $taxMapper = new TaxMapper();
      $taxMapper->fetchConnecResource($entity_id);
      break;
    case "ACCOUNTS":
      $accountMapper = new AccountMapper();
      $accountMapper->fetchConnecResource($entity_id);
      break;
    case "ORGANIZATIONS":
      $organizationMapper = new OrganizationMapper();
      $organizationMapper->fetchConnecResource($entity_id);
      break;
    case "PERSONS":
      $contactMapper = new ContactMapper();
      $contactMapper->fetchConnecResource($entity_id);
      break;
    case "ITEMS":
      $productMapper = new ProductMapper();
      $productMapper->fetchConnecResource($entity_id);
      break;
    case "SALESORDERS":
      $salesOrderMapper = new SalesOrderMapper();
      $salesOrderMapper->fetchConnecResource($entity_id);
      break;
    case "INVOICES":
      $customerInvoiceMapper = new CustomerInvoiceMapper();
      $customerInvoiceMapper->fetchConnecResource($entity_id);
      $supplierInvoiceMapper = new SupplierInvoiceMapper();
      $supplierInvoiceMapper->fetchConnecResource($entity_id);
      break;
    case "PAYMENTS":
      $customerPaymentMapper = new CustomerPaymentMapper();
      $customerPaymentMapper->fetchConnecResource($entity_id);
      $supplierPaymentMapper = new SupplierPaymentMapper();
      $supplierPaymentMapper->fetchConnecResource($entity_id);
      break;
    case "WAREHOUSES":
      $warehouseMapper = new WarehouseMapper();
      $warehouseMapper->fetchConnecResource($entity_id);
      break;
  }
} catch (Exception $e) {
  error_log("Caught exception in subscribe " . json_encode($e->getMessage()));
}
