<?php

// Include required libraries
require_once MAESTRANO_ROOT . '/connec/ConnecUtils.php';
require_once MAESTRANO_ROOT . '/connec/MnoIdMap.php';
require_once MAESTRANO_ROOT . '/connec/BaseMapper.php';
require_once MAESTRANO_ROOT . '/connec/CompanyMapper.php';
require_once MAESTRANO_ROOT . '/connec/AccountMapper.php';
require_once MAESTRANO_ROOT . '/connec/TaxMapper.php';
require_once MAESTRANO_ROOT . '/connec/OrganizationMapper.php';
require_once MAESTRANO_ROOT . '/connec/ContactMapper.php';
require_once MAESTRANO_ROOT . '/connec/ProductMapper.php';
require_once MAESTRANO_ROOT . '/connec/TransactionMapper.php';
require_once MAESTRANO_ROOT . '/connec/CustomerInvoiceMapper.php';
require_once MAESTRANO_ROOT . '/connec/CustomerInvoiceLineMapper.php';
require_once MAESTRANO_ROOT . '/connec/SupplierInvoiceMapper.php';
require_once MAESTRANO_ROOT . '/connec/SupplierInvoiceLineMapper.php';
require_once MAESTRANO_ROOT . '/connec/PaymentMapper.php';

// Set default user as a global variable
$user = ConnecUtils::defaultUser();