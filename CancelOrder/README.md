# Magento 2 Admin Order Tools – Cancel & Move to Processing

This Magento 2 module adds custom admin order actions:  
✅ Cancel Order  
✅ Move Order to Processing (with custom export tracking)

## 🔧 Features

- Adds "Cancel Order" button even when Magento restricts it
- Adds "Move to Processing" for orders stuck in `payment_review`
- Automatically logs processed orders into a custom DB table
- Built with Magento 2 best practices: dependency injection, admin ACL, routing, config sources

## 📁 Module Structure

```
Aiops/CancelOrder/
├── Block/Adminhtml/Order/View.php
├── Controller/Adminhtml/Order/Cancel.php
├── Controller/Adminhtml/Order/Movetoprocessing.php
├── Model/Config/Source/Order/Status.php
├── etc/
│   ├── adminhtml/routes.xml
│   ├── adminhtml/system.xml
│   ├── di.xml
│   ├── module.xml
│   ├── routes.xml
├── composer.json
├── registration.php
```

## 🧪 How It Works

### Cancel Order
Force-cancels orders even when `canCancel()` returns false, if needed.  
Uses `orderManagement->cancel()` and handles exceptions gracefully.

### Move to Processing
Moves order from `payment_review` to `processing` by:
- Registering authorization and capture on the payment object
- Updating order state and status to `processing`
- Inserting a tracking record into a custom DB table (`synolia_sync_processed`)  
  → This is useful when orders need to be marked as exported to ERP or external systems.

> ⚠️ You may want to update the DB table name and flow identifiers (`synolia_sync_processed`, `m3_export_orders`) to match your use case. They are provided as working examples.

## 🛠️ Installation

### Option 1: Place in `app/code`
1. Copy the `Aiops/CancelOrder` folder to `app/code/Aiops/CancelOrder`
2. Run:
   ```
   php bin/magento module:enable Aiops_CancelOrder
   php bin/magento setup:upgrade
   php bin/magento cache:flush
   ```

### Option 2: Via Composer
> Only applicable if you publish this module on Packagist or a private repo.

```
composer require your-vendor/module-cancel-order
php bin/magento module:enable Aiops_CancelOrder
php bin/magento setup:upgrade
```

## 🔐 Permissions

Admin ACL:  
`Magento_Sales::cancel`  

Make sure your admin role has permission to cancel and process orders via the Sales module.

## ✔️ Compatibility

- Magento 2.4.x (tested)
- No core overrides
- Uses PSR-compliant coding standards and DI best practices

## 💬 Feedback & Contribution

Feel free to fork this repo, open issues or submit PRs with improvements.  
This module was created as a practical showcase of real-world Magento 2 admin functionality.

## 📘 License

MIT – Free to use, share, and modify.  
Attribution appreciated but not required.
