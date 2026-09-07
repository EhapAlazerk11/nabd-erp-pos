# Database Schema Inventory — `nabd_dev`

> Generated from code analysis. This is the single source of truth for which
> service owns which table. **Rule: never migrate a table another service owns.**

## Table Ownership Summary

| Prefix | Owner | Migrated by | Others may |
|--------|-------|-------------|------------|
| `nexopos_*` | `nabd-erp-pos` | Laravel/Eloquent migrations | Read/write rows, never alter schema |
| `nabd_*` | `nabd-platform-core` | Its own migrations (TBD) | Read via API or direct SELECT |
| `nabd_store_*` | `nabd-store` | Sequelize `sync()` | — |

---

## NexoPOS Tables (`nabd-erp-pos` owns)

### Commerce Core
| Table | Model | Key Columns |
|-------|-------|-------------|
| `nexopos_products` | `Product` | id (bigint), name, sku, barcode, category_id, tax_group_id, status, stock_management, type, product_type |
| `nexopos_products_categories` | `ProductCategory` | id, name, parent_id, media_id |
| `nexopos_products_unit_quantities` | `ProductUnitQuantity` | id, product_id, unit_id, quantity, sale_price, purchase_price |
| `nexopos_products_taxes` | `ProductTax` | id, product_id, tax_id, rate |
| `nexopos_products_gallery` | `ProductGallery` | id, product_id, media_id |
| `nexopos_products_history` | `ProductHistory` | id, product_id, action, quantity |
| `nexopos_products_history_combined` | `ProductHistoryCombined` | id, product_id |
| `nexopos_products_metas` | — | id, product_id, key, value |
| `nexopos_products_group_items` | `ProductSubItem` | id, parent_id, product_id, quantity |
| `nexopos_products_adjustments` | `ProductAdjustment` | id, author_id, title, status |
| `nexopos_products_adjustment_items` | `ProductAdjustmentItem` | id, adjustment_id, product_id, unit_id |

### Orders & Transactions
| Table | Model | Key Columns |
|-------|-------|-------------|
| `nexopos_orders` | `Order` | id, code, customer_id, total, payment_status, process_status, delivery_status |
| `nexopos_orders_count` | — | id, count, date |
| `nexopos_orders_products` | `OrderProduct` | id, order_id, product_id, quantity, unit_price |
| `nexopos_orders_payments` | `OrderPayment` | id, order_id, identifier, value |
| `nexopos_orders_addresses` | `OrderAddress` | id, order_id, type |
| `nexopos_orders_coupons` | `OrderCoupon` | id, order_id, coupon_id |
| `nexopos_orders_refunds` | `OrderRefund` | id, order_id, total, payment_method |
| `nexopos_orders_products_refunds` | `OrderProductRefund` | id, order_product_id, quantity |
| `nexopos_orders_taxes` | `OrderTax` | id, order_id, tax_id, rate |
| `nexopos_orders_instalments` | `OrderInstalment` | id, order_id, amount |
| `nexopos_orders_metas` | — | id, order_id, key, value |
| `nexopos_orders_storage` | `OrderStorage` | id, order_id |
| `nexopos_orders_settings` | `OrderSetting` | id, order_id |

### Customers
| Table | Model | Key Columns |
|-------|-------|-------------|
| `nexopos_customers` | `Customer` | id, name, email, phone, group_id |
| `nexopos_customers_groups` | `CustomerGroup` | id, name |
| `nexopos_customers_addresses` | `CustomerAddress` | id, customer_id |
| `nexopos_customers_billing_addresses` | `CustomerBillingAddress` | id, customer_id |
| `nexopos_customers_shipping_addresses` | `CustomerShippingAddress` | id, customer_id |
| `nexopos_customers_account_history` | `CustomerAccountHistory` | id, customer_id, operation, amount |
| `nexopos_customers_coupons` | `CustomerCoupon` | id, customer_id, coupon_id |
| `nexopos_customers_rewards` | `CustomerReward` | id, customer_id |

### Procurement
| Table | Model | Key Columns |
|-------|-------|-------------|
| `nexopos_procurements` | `Procurement` | id, provider_id, status |
| `nexopos_procurements_products` | `ProcurementProduct` | id, procurement_id, product_id |
| `nexopos_providers` | `Provider` | id, name, email |

### Finance & Accounting
| Table | Model | Key Columns |
|-------|-------|-------------|
| `nexopos_transactions` | `Transaction` | id, name, type, status |
| `nexopos_transactions_accounts` | `TransactionAccount` | id, name |
| `nexopos_transactions_histories` | `TransactionHistory` | id, transaction_id |
| `nexopos_transactions_action_rules` | `TransactionActionRule` | id |
| `nexopos_transactions_balance_days` | `TransactionBalanceDay` | id |
| `nexopos_transactions_balance_months` | `TransactionBalanceMonth` | id |
| `nexopos_payments_types` | `PaymentType` | id, identifier, label |

### Coupons & Rewards
| Table | Model | Key Columns |
|-------|-------|-------------|
| `nexopos_coupons` | `Coupon` | id, code, discount_type, discount_value |
| `nexopos_coupons_categories` | `CouponCategory` | id, coupon_id |
| `nexopos_coupons_customers` | `CouponCustomer` | id, coupon_id |
| `nexopos_coupons_customers_groups` | `CouponCustomerGroup` | id, coupon_id |
| `nexopos_coupons_products` | `CouponProduct` | id, coupon_id |
| `nexopos_rewards_system` | `RewardSystem` | id, name |
| `nexopos_rewards_system_rules` | `RewardSystemRule` | id, reward_id |

### POS & Registers
| Table | Model | Key Columns |
|-------|-------|-------------|
| `nexopos_registers` | `Register` | id, name, status |
| `nexopos_registers_history` | `RegisterHistory` | id, register_id, action |

### Units & Taxes
| Table | Model | Key Columns |
|-------|-------|-------------|
| `nexopos_units` | `Unit` | id, name, identifier |
| `nexopos_units_groups` | `UnitGroup` | id, name |
| `nexopos_taxes` | `Tax` | id, name, rate |
| `nexopos_taxes_groups` | `TaxGroup` | id, name |
| `nexopos_scale_ranges` | `ScaleRange` | id, name |

### Dashboard & Reporting
| Table | Model | Key Columns |
|-------|-------|-------------|
| `nexopos_dashboard_days` | `DashboardDay` | id, day_of_year, year |
| `nexopos_dashboard_months` | `DashboardMonth` | id, month |

### Users & Auth (NexoPOS staff)
| Table | Model | Key Columns |
|-------|-------|-------------|
| `nexopos_users` | `User` | id, username, email, role_id |
| `nexopos_users_attributes` | `UserAttribute` | id, user_id |
| `nexopos_users_widgets` | `UserWidget` | id, user_id |
| `nexopos_roles` | `Role` | id, name, namespace |
| `nexopos_permissions` | `Permission` | id, name, namespace |
| `nexopos_role_permission` | `RolePermission` | role_id, permission_id |
| `nexopos_user_role_relations` | `UserRoleRelation` | user_id, role_id |
| `nexopos_user_scopes` | `UserScope` | id, user_id |
| `personal_access_tokens` | `PersonalAccessToken` | id, tokenable_id |

### System
| Table | Model | Key Columns |
|-------|-------|-------------|
| `nexopos_options` | `Option` | id, key, value |
| `nexopos_medias` | `Media` | id, name, extension |
| `nexopos_notifications` | `Notification` | id, title, source |
| `nexopos_modules_migrations` | `ModuleMigration` | id, namespace |
| `nexopos_migrations` | `Migration` | id, migration |
| `telescope_entries` | — | sequence, uuid, type |
| `telescope_entries_tags` | — | entry_uuid, tag |
| `telescope_monitoring` | — | tag |
| `failed_jobs` | — | id, uuid, connection |
| `jobs` | — | id, queue, payload |

### NabdBridge
| Table | Model | Key Columns |
|-------|-------|-------------|
| `nabd_api_tokens` | `NabdApiToken` | id, name, token (SHA-256), expires_at |

---

## nabd-store Tables (Store owns)

| Table | Model | Key Columns | Notes |
|-------|-------|-------------|-------|
| `nabd_store_users` | `User` | id (UUID), email, firstName, lastName, role, provider | Storefront shoppers — NOT POS staff |
| `nabd_store_products` | `Product` | id (UUID), name, name_ar, slug, price, quantity, brandId | Store-specific product data. Will link to `nexopos_products` via STO-1 |
| `nabd_store_categories` | `Category` | id (UUID), name, name_ar, slug | Store-specific categories. Will link to `nexopos_products_categories` via STO-2 |
| `nabd_store_orders` | `Order` | id (UUID), total, cartId, userId, paymentMethod | E-commerce orders. Will route through NabdBridge via STO-3 |
| `nabd_store_carts` | `Cart` | id (UUID), userId | Shopping carts |
| `nabd_store_cart_items` | `CartItem` | id (UUID), cartId, productId, quantity, purchasePrice | Cart line items |
| `nabd_store_brands` | `Brand` | id (UUID), name, name_ar, slug | No NexoPOS equivalent |
| `nabd_store_merchants` | `Merchant` | id (UUID), name, email, status | Marketplace merchants |
| `nabd_store_addresses` | `Address` | id (UUID), userId, address, city, country | Shipping/billing addresses |
| `nabd_store_reviews` | `Review` | id (UUID), productId, userId, rating, review | Product reviews |
| `nabd_store_wishlists` | `Wishlist` | id (UUID), productId, userId | User wishlists |
| `nabd_store_contacts` | `Contact` | id (UUID), name, email, message | Contact form submissions |
| `nabd_store_category_products` | — | categoryId, productId | Junction table (M2M) |

---

## nabd-platform-core Tables (TBD — currently MongoDB)

> These tables will be created when `nabd-platform-core` migrates from MongoDB
> to PostgreSQL (CORE-1 → CORE-5). All will use `nabd_` prefix.

| Planned Table | Current Mongo Collection | Notes |
|---------------|-------------------------|-------|
| `nabd_users` | `User` | Platform accounts (master identity) |
| `nabd_businesses` | `Business` | Business/company records |
| `nabd_social_accounts` | `SocialAccount` | Connected social media accounts |
| `nabd_content` | `Content` | Social media content/posts |
| `nabd_scheduled_posts` | `ScheduledPost` | Scheduled social publications |
| `nabd_insights` | `Insight` | Analytics insights |
| `nabd_ads` | `Ad` | Ad campaigns |
| `nabd_advertisers` | `Advertiser` | Ad account connections |
| `nabd_keywords` | `Keyword` | Tracked keywords |
| `nabd_comments` | `Comment` | Social media comments |
| `nabd_ai_generations` | `AIGeneration` | AI content generation history |
| `nabd_scrape_runs` | `ScrapeRun` | Competitor scraping runs |
| `nabd_notifications` | `Notification` | Platform notifications |
| `nabd_oauth_states` | `OAuthState` | OAuth flow state management |

---

## Multi-tenancy Status (TEN-1)

> **No table currently has `business_id`.** This column must be added to every
> business-owned table before production. See TEN-1 → TEN-4.

**Tables that NEED `business_id`:** All `nexopos_*` tables (except system tables
like roles, permissions, options, medias) and all `nabd_store_*` tables.

**Tables that do NOT need `business_id`:** System/config tables, `nabd_api_tokens`,
roles, permissions, payment types.
