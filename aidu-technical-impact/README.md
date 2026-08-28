# AID-U Technical Impact — WhatsApp Only

## Installation
1. Extract this folder into `C:\wamp64\www\`.
2. Start Apache and MySQL in WAMP.
3. Open phpMyAdmin.
4. Create/select your AID-U database.
5. Import the single `database.sql` file.
6. Open the website in your browser.
7. Visit `admin/register.php` to create the first administrator.
8. Login through `admin/login.php`.
9. Configure company and WhatsApp details from Admin > Settings.

## Important
- This version does not use Hubtel or SMS.
- Enquiries are stored in MySQL and the direct messaging option is WhatsApp.
- The SQL settings INSERT has been corrected; there are no trailing commas after the final column/value.
