LUZANO SPEAR MASTER — now functional like Abella Apparel
==========================================================

WHAT CHANGED
------------
The site went from a static products.json file to a real MySQL-backed
e-commerce flow: accounts, cart, checkout, orders, and a fuller admin
panel — the same shape as your Abella Apparel project, but branded blue
for Luzano and with a couple of security fixes baked in.

New/changed files:
  schema.sql          <- run this once to create the database + tables
  config.php           <- DB connection (update credentials if needed)
  login.php / login_register.php / logout.php   <- shared login for
                                                     customers AND admins
  index.php             <- now reads products from the database
  product.php            <- single product page (new)
  add_to_cart.php, update_cart.php, cart.php     <- session-based cart (new)
  checkout.php / place_order.php / order_success.php   <- order flow (new)
  user_page.php          <- account page: order history + messages (new)
  send_message.php       <- contact form now writes to the database
  admin_page.php          <- admin dashboard (replaces admin.php)
  admin_stock.php          <- product/stock CRUD (replaces admin.php's form)
  admin_orders.php         <- update order status (new)
  admin_customers.php      <- customer directory (new)
  admin_messages.php       <- reply to customer messages (new)
  partials/nav.php, partials/admin_nav.php, partials/admin_guard.php
  style.css              <- extended with cart/account/product-detail styles
  auth.css                <- new login/register stylesheet (blue theme)
  admin.css               <- unchanged
  script.js               <- contact form now submits for real; added
                              the showForm() helper login.php needs

  admin.php               <- compatibility redirect to login.php; use
                              admin_page.php after signing in.
  products.json           <- removed; products are loaded from MySQL.


SETUP STEPS
-----------
1. Create the database:
     mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS luzano_db"
     mysql -u root -p luzano_db < schema.sql

2. Check config.php matches your MySQL host/user/password.

3. Copy all these files into your site folder alongside your existing
   /images directory (unchanged — reel.png, wetsuit.png, viclogo.png,
   diving.jpg, etc. all still live there).

4. Log in as admin at login.php:
     Email:    admin@luzano.com
     Password: Admin123!
   CHANGE THIS PASSWORD as soon as you can — either add a "change
   password" form later, or update it directly in the database with:
     UPDATE users SET password = '<new bcrypt hash>' WHERE email = 'admin@luzano.com';

5. Customers register through the same login.php (Register tab) and are
   always created with role = 'user'. Admin accounts are only created
   directly in the database — there's no public way to register as admin.


SECURITY FIXES vs. THE ABELLA VERSION
--------------------------------------
- login_register.php now uses prepared statements everywhere (the Abella
  version built raw SQL strings for login/register, which is injectable).
- The registration form no longer lets a visitor pick "Admin" as a role.
  Every self-registered account is a regular user.


HOW THE PIECES FIT TOGETHER
----------------------------
- Cart lives in $_SESSION['cart'] as a list of
  {product_id, name, price, image, quantity} — same shape Abella used.
- place_order.php locks each product row (FOR UPDATE), re-checks stock,
  and only commits if every line item still has enough stock — same
  transactional pattern as Abella's place_order.php.
- Messages are a single table (sender_id/receiver_id) shared by the
  public contact form, the account-page message box, and the admin
  reply screen — mirroring Abella's messages table.
