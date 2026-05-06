# Complete Structure

This indexed tree reflects the release repository structure. It excludes `.git`, `vendor`, ignored local environment files such as `.env`, and runtime storage artifacts.

Placeholder `README.md` files that remain in repeated architecture folders are intentional and help keep the full framework shape visible in the repository.

Inside `App/Templates`, the tree includes the tracked native `.vide` templates and compatibility `.lmv`/`.php` counterparts so release structure audits see the full presentation surface.

Presentation managers are canonical under `App/Utilities/Managers/Presentation`; `App/Support/Theming/ThemeManager.php` remains in the tree as a backward-compatible alias.

Commerce operational managers and calculators are canonical under `App/Utilities/Managers/Commerce`; matching `App/Support/Commerce/*Manager.php` files and `CommerceTotalsCalculator.php` remain as backward-compatible aliases.

```text
LangelerMVC
├── .github
│   └── workflows
│       └── php.yml
├── App
│   ├── Abstracts
│   │   ├── Console
│   │   │   └── Command.php
│   │   ├── Data
│   │   │   ├── Cache.php
│   │   │   ├── Crypto.php
│   │   │   ├── Finder.php
│   │   │   ├── Sanitizer.php
│   │   │   ├── SchemaProcessor.php
│   │   │   └── Validator.php
│   │   ├── Database
│   │   │   ├── Migration.php
│   │   │   ├── Model.php
│   │   │   ├── Query.php
│   │   │   ├── Repository.php
│   │   │   └── Seed.php
│   │   ├── Http
│   │   │   ├── Controller.php
│   │   │   ├── InboundRequest.php
│   │   │   ├── Middleware.php
│   │   │   ├── Request.php
│   │   │   ├── Response.php
│   │   │   ├── Service.php
│   │   │   └── StandardResponse.php
│   │   ├── Presentation
│   │   │   ├── Presenter.php
│   │   │   ├── Resource.php
│   │   │   ├── ResourceCollection.php
│   │   │   └── View.php
│   │   └── Support
│   │       ├── CarrierAdapter.php
│   │       ├── Mailable.php
│   │       ├── Notification.php
│   │       └── PaymentDriver.php
│   ├── Console
│   │   ├── Commands
│   │   │   ├── AuditListCommand.php
│   │   │   ├── AuditPruneCommand.php
│   │   │   ├── CacheClearCommand.php
│   │   │   ├── ConfigShowCommand.php
│   │   │   ├── EventListCommand.php
│   │   │   ├── FrameworkArchitectureCommand.php
│   │   │   ├── FrameworkDoctorCommand.php
│   │   │   ├── FrameworkLayersCommand.php
│   │   │   ├── HealthCheckCommand.php
│   │   │   ├── MigrateCommand.php
│   │   │   ├── MigrateRollbackCommand.php
│   │   │   ├── MigrateStatusCommand.php
│   │   │   ├── ModuleListCommand.php
│   │   │   ├── ModuleMakeCommand.php
│   │   │   ├── NotificationListCommand.php
│   │   │   ├── QueueDrainCommand.php
│   │   │   ├── QueueFailedCommand.php
│   │   │   ├── QueuePruneFailedCommand.php
│   │   │   ├── QueueRetryCommand.php
│   │   │   ├── QueueStopCommand.php
│   │   │   ├── QueueWorkCommand.php
│   │   │   ├── ReleaseCheckCommand.php
│   │   │   ├── RouteListCommand.php
│   │   │   └── SeedCommand.php
│   │   └── ConsoleKernel.php
│   ├── Contracts
│   │   ├── Async
│   │   │   ├── EventDispatcherInterface.php
│   │   │   ├── FailedJobStoreInterface.php
│   │   │   ├── JobInterface.php
│   │   │   ├── ListenerInterface.php
│   │   │   └── QueueDriverInterface.php
│   │   ├── Auth
│   │   │   ├── AuthenticatableInterface.php
│   │   │   ├── GuardInterface.php
│   │   │   ├── PasswordBrokerInterface.php
│   │   │   └── UserProviderInterface.php
│   │   ├── Console
│   │   │   └── CommandInterface.php
│   │   ├── Data
│   │   │   ├── CacheDriverInterface.php
│   │   │   ├── CryptoInterface.php
│   │   │   ├── FinderInterface.php
│   │   │   ├── SanitizerInterface.php
│   │   │   └── ValidatorInterface.php
│   │   ├── Database
│   │   │   ├── MigrationInterface.php
│   │   │   ├── ModelInterface.php
│   │   │   ├── RepositoryInterface.php
│   │   │   └── SeedInterface.php
│   │   ├── Http
│   │   │   ├── ControllerInterface.php
│   │   │   ├── MiddlewareInterface.php
│   │   │   ├── RequestInterface.php
│   │   │   ├── ResponseInterface.php
│   │   │   └── ServiceInterface.php
│   │   ├── Presentation
│   │   │   ├── AssetManagerInterface.php
│   │   │   ├── HtmlManagerInterface.php
│   │   │   ├── PresenterInterface.php
│   │   │   ├── ResourceInterface.php
│   │   │   ├── TemplateEngineInterface.php
│   │   │   └── ViewInterface.php
│   │   ├── Session
│   │   │   └── SessionDriverInterface.php
│   │   └── Support
│   │       ├── ArchitectureAlignmentManagerInterface.php
│   │       ├── AuditLoggerInterface.php
│   │       ├── CarrierAdapterInterface.php
│   │       ├── FrameworkDoctorInterface.php
│   │       ├── FrameworkLayerManagerInterface.php
│   │       ├── HealthManagerInterface.php
│   │       ├── MailerInterface.php
│   │       ├── NotifiableInterface.php
│   │       ├── NotificationChannelInterface.php
│   │       ├── NotificationInterface.php
│   │       ├── NotificationManagerInterface.php
│   │       ├── OtpManagerInterface.php
│   │       ├── PasskeyDriverInterface.php
│   │       ├── PasskeyManagerInterface.php
│   │       ├── PaymentDriverInterface.php
│   │       └── PaymentManagerInterface.php
│   ├── Core
│   │   ├── Schema
│   │   │   └── Blueprint.php
│   │   ├── App.php
│   │   ├── Bootstrap.php
│   │   ├── Config.php
│   │   ├── Container.php
│   │   ├── Database.php
│   │   ├── FrameworkResponse.php
│   │   ├── MigrationRunner.php
│   │   ├── ModuleManager.php
│   │   ├── Router.php
│   │   ├── SeedRunner.php
│   │   └── Session.php
│   ├── Drivers
│   │   ├── Caching
│   │   │   ├── ArrayCache.php
│   │   │   ├── DatabaseCache.php
│   │   │   ├── FileCache.php
│   │   │   ├── MemCache.php
│   │   │   └── RedisCache.php
│   │   ├── Cryptography
│   │   │   ├── OpenSSLCrypto.php
│   │   │   └── SodiumCrypto.php
│   │   ├── Notifications
│   │   │   ├── DatabaseNotificationChannel.php
│   │   │   └── MailNotificationChannel.php
│   │   ├── Passkeys
│   │   │   ├── TestingPasskeyDriver.php
│   │   │   └── WebAuthnPasskeyDriver.php
│   │   ├── Payments
│   │   │   ├── CardPaymentDriver.php
│   │   │   ├── CryptoPaymentDriver.php
│   │   │   ├── KlarnaPaymentDriver.php
│   │   │   ├── PayPalPaymentDriver.php
│   │   │   ├── QliroPaymentDriver.php
│   │   │   ├── SwishPaymentDriver.php
│   │   │   ├── TestingPaymentDriver.php
│   │   │   └── WalleyPaymentDriver.php
│   │   ├── Queue
│   │   │   ├── DatabaseQueueDriver.php
│   │   │   └── SyncQueueDriver.php
│   │   ├── Session
│   │   │   ├── DatabaseSessionDriver.php
│   │   │   ├── EncryptedSessionDriver.php
│   │   │   ├── FileSessionDriver.php
│   │   │   ├── README.md
│   │   │   └── RedisSessionDriver.php
│   │   └── Shipping
│   │       ├── AirmeeCarrierAdapter.php
│   │       ├── BringCarrierAdapter.php
│   │       ├── BudbeeCarrierAdapter.php
│   │       ├── DhlCarrierAdapter.php
│   │       ├── EarlyBirdCarrierAdapter.php
│   │       ├── InstaboxCarrierAdapter.php
│   │       ├── PostNordCarrierAdapter.php
│   │       ├── SchenkerCarrierAdapter.php
│   │       └── UpsCarrierAdapter.php
│   ├── Exceptions
│   │   ├── Data
│   │   │   ├── CacheException.php
│   │   │   ├── CryptoException.php
│   │   │   ├── FinderException.php
│   │   │   ├── SanitizationException.php
│   │   │   └── ValidationException.php
│   │   ├── Database
│   │   │   ├── DatabaseException.php
│   │   │   ├── MigrationException.php
│   │   │   ├── ModelException.php
│   │   │   ├── RepositoryException.php
│   │   │   └── SeedException.php
│   │   ├── Http
│   │   │   ├── ControllerException.php
│   │   │   ├── MiddlewareException.php
│   │   │   ├── RequestException.php
│   │   │   ├── ResponseException.php
│   │   │   └── ServiceException.php
│   │   ├── Iterator
│   │   │   ├── IteratorException.php
│   │   │   └── IteratorNotFoundException.php
│   │   ├── Presentation
│   │   │   ├── PresenterException.php
│   │   │   └── ViewException.php
│   │   ├── Support
│   │   │   └── PaymentException.php
│   │   ├── AppException.php
│   │   ├── AuthException.php
│   │   ├── ConfigException.php
│   │   ├── ContainerException.php
│   │   ├── RouteNotFoundException.php
│   │   ├── RouterException.php
│   │   └── SessionException.php
│   ├── Framework
│   │   └── Migrations
│   │       └── CreateFrameworkOperationsTables.php
│   ├── Helpers
│   │   └── README.md
│   ├── Installer
│   │   ├── InstallerView.php
│   │   └── InstallerWizard.php
│   ├── Modules
│   │   ├── AdminModule
│   │   │   ├── Controllers
│   │   │   │   ├── AdminController.php
│   │   │   │   └── README.md
│   │   │   ├── Middlewares
│   │   │   │   ├── AdminAccessMiddleware.php
│   │   │   │   └── README.md
│   │   │   ├── Migrations
│   │   │   │   └── README.md
│   │   │   ├── Models
│   │   │   │   └── README.md
│   │   │   ├── Presenters
│   │   │   │   ├── AdminPresenter.php
│   │   │   │   ├── AdminResource.php
│   │   │   │   └── README.md
│   │   │   ├── Repositories
│   │   │   │   └── README.md
│   │   │   ├── Requests
│   │   │   │   ├── AdminRequest.php
│   │   │   │   └── README.md
│   │   │   ├── Responses
│   │   │   │   ├── AdminResponse.php
│   │   │   │   └── README.md
│   │   │   ├── Routes
│   │   │   │   ├── README.md
│   │   │   │   └── web.php
│   │   │   ├── Seeds
│   │   │   │   └── README.md
│   │   │   ├── Services
│   │   │   │   ├── AdminAccessService.php
│   │   │   │   └── README.md
│   │   │   └── Views
│   │   │       ├── AdminView.php
│   │   │       └── README.md
│   │   ├── CartModule
│   │   │   ├── Controllers
│   │   │   │   ├── CartController.php
│   │   │   │   └── README.md
│   │   │   ├── Listeners
│   │   │   │   ├── MergeCartOnLoginListener.php
│   │   │   │   └── README.md
│   │   │   ├── Middlewares
│   │   │   │   └── README.md
│   │   │   ├── Migrations
│   │   │   │   ├── AddCartDiscountColumns.php
│   │   │   │   ├── CreateCartTables.php
│   │   │   │   ├── CreatePromotionTables.php
│   │   │   │   ├── CreatePromotionUsageTable.php
│   │   │   │   └── README.md
│   │   │   ├── Models
│   │   │   │   ├── Cart.php
│   │   │   │   ├── CartItem.php
│   │   │   │   ├── Promotion.php
│   │   │   │   └── README.md
│   │   │   ├── Notifications
│   │   │   │   ├── CartMergedNotification.php
│   │   │   │   └── README.md
│   │   │   ├── Presenters
│   │   │   │   ├── CartPresenter.php
│   │   │   │   ├── CartResource.php
│   │   │   │   └── README.md
│   │   │   ├── Repositories
│   │   │   │   ├── CartItemRepository.php
│   │   │   │   ├── CartRepository.php
│   │   │   │   ├── PromotionRepository.php
│   │   │   │   └── README.md
│   │   │   ├── Requests
│   │   │   │   ├── CartRequest.php
│   │   │   │   └── README.md
│   │   │   ├── Responses
│   │   │   │   ├── CartResponse.php
│   │   │   │   └── README.md
│   │   │   ├── Routes
│   │   │   │   ├── README.md
│   │   │   │   └── web.php
│   │   │   ├── Seeds
│   │   │   │   ├── CartSeed.php
│   │   │   │   └── README.md
│   │   │   ├── Services
│   │   │   │   ├── CartService.php
│   │   │   │   └── README.md
│   │   │   └── Views
│   │   │       ├── CartView.php
│   │   │       └── README.md
│   │   ├── OrderModule
│   │   │   ├── Controllers
│   │   │   │   ├── OrderController.php
│   │   │   │   └── README.md
│   │   │   ├── Listeners
│   │   │   │   ├── OrderLifecycleNotificationListener.php
│   │   │   │   └── README.md
│   │   │   ├── Middlewares
│   │   │   │   └── README.md
│   │   │   ├── Migrations
│   │   │   │   ├── AddOrderCommerceStateColumns.php
│   │   │   │   ├── AddOrderDiscountSnapshotColumns.php
│   │   │   │   ├── AddOrderShipmentTrackingColumns.php
│   │   │   │   ├── CreateInventoryReservationsTable.php
│   │   │   │   ├── CreateOrderAdjustmentTables.php
│   │   │   │   ├── CreateOrderEntitlementsTable.php
│   │   │   │   ├── CreateOrderSubscriptionsTable.php
│   │   │   │   ├── CreateOrderTables.php
│   │   │   │   ├── CreatePaymentWebhookEventsTable.php
│   │   │   │   └── README.md
│   │   │   ├── Models
│   │   │   │   ├── InventoryReservation.php
│   │   │   │   ├── Order.php
│   │   │   │   ├── OrderAddress.php
│   │   │   │   ├── OrderDocument.php
│   │   │   │   ├── OrderEntitlement.php
│   │   │   │   ├── OrderItem.php
│   │   │   │   ├── OrderReturn.php
│   │   │   │   ├── OrderSubscription.php
│   │   │   │   ├── PaymentWebhookEvent.php
│   │   │   │   └── README.md
│   │   │   ├── Notifications
│   │   │   │   ├── OrderStatusNotification.php
│   │   │   │   └── README.md
│   │   │   ├── Presenters
│   │   │   │   ├── OrderPresenter.php
│   │   │   │   ├── OrderResource.php
│   │   │   │   └── README.md
│   │   │   ├── Repositories
│   │   │   │   ├── InventoryReservationRepository.php
│   │   │   │   ├── OrderAddressRepository.php
│   │   │   │   ├── OrderDocumentRepository.php
│   │   │   │   ├── OrderEntitlementRepository.php
│   │   │   │   ├── OrderItemRepository.php
│   │   │   │   ├── OrderRepository.php
│   │   │   │   ├── OrderReturnRepository.php
│   │   │   │   ├── OrderSubscriptionRepository.php
│   │   │   │   ├── PaymentWebhookEventRepository.php
│   │   │   │   └── README.md
│   │   │   ├── Requests
│   │   │   │   ├── OrderRequest.php
│   │   │   │   └── README.md
│   │   │   ├── Responses
│   │   │   │   ├── OrderResponse.php
│   │   │   │   └── README.md
│   │   │   ├── Routes
│   │   │   │   ├── README.md
│   │   │   │   └── web.php
│   │   │   ├── Seeds
│   │   │   │   ├── OrderSeed.php
│   │   │   │   └── README.md
│   │   │   ├── Services
│   │   │   │   ├── OrderService.php
│   │   │   │   └── README.md
│   │   │   └── Views
│   │   │       ├── OrderView.php
│   │   │       └── README.md
│   │   ├── ShopModule
│   │   │   ├── Controllers
│   │   │   │   ├── README.md
│   │   │   │   └── ShopController.php
│   │   │   ├── Listeners
│   │   │   │   ├── CatalogActivityNotificationListener.php
│   │   │   │   └── README.md
│   │   │   ├── Middlewares
│   │   │   │   └── README.md
│   │   │   ├── Migrations
│   │   │   │   ├── AddProductFulfillmentColumns.php
│   │   │   │   ├── CreateShopTables.php
│   │   │   │   └── README.md
│   │   │   ├── Models
│   │   │   │   ├── Category.php
│   │   │   │   ├── Product.php
│   │   │   │   └── README.md
│   │   │   ├── Notifications
│   │   │   │   ├── CatalogActivityNotification.php
│   │   │   │   └── README.md
│   │   │   ├── Presenters
│   │   │   │   ├── README.md
│   │   │   │   ├── ShopPresenter.php
│   │   │   │   └── ShopResource.php
│   │   │   ├── Repositories
│   │   │   │   ├── CategoryRepository.php
│   │   │   │   ├── ProductRepository.php
│   │   │   │   └── README.md
│   │   │   ├── Requests
│   │   │   │   ├── README.md
│   │   │   │   └── ShopRequest.php
│   │   │   ├── Responses
│   │   │   │   ├── README.md
│   │   │   │   └── ShopResponse.php
│   │   │   ├── Routes
│   │   │   │   ├── README.md
│   │   │   │   └── web.php
│   │   │   ├── Seeds
│   │   │   │   ├── README.md
│   │   │   │   └── ShopSeed.php
│   │   │   ├── Services
│   │   │   │   ├── CatalogService.php
│   │   │   │   └── README.md
│   │   │   └── Views
│   │   │       ├── README.md
│   │   │       └── ShopView.php
│   │   ├── UserModule
│   │   │   ├── Controllers
│   │   │   │   ├── AuthController.php
│   │   │   │   ├── PasskeyController.php
│   │   │   │   ├── ProfileController.php
│   │   │   │   └── README.md
│   │   │   ├── Middlewares
│   │   │   │   ├── AuthenticateMiddleware.php
│   │   │   │   └── README.md
│   │   │   ├── Migrations
│   │   │   │   ├── CreateUserPlatformTables.php
│   │   │   │   └── README.md
│   │   │   ├── Models
│   │   │   │   ├── Permission.php
│   │   │   │   ├── README.md
│   │   │   │   ├── Role.php
│   │   │   │   ├── User.php
│   │   │   │   ├── UserAuthToken.php
│   │   │   │   └── UserPasskey.php
│   │   │   ├── Presenters
│   │   │   │   ├── README.md
│   │   │   │   ├── UserPresenter.php
│   │   │   │   └── UserResource.php
│   │   │   ├── Repositories
│   │   │   │   ├── PermissionRepository.php
│   │   │   │   ├── README.md
│   │   │   │   ├── RoleRepository.php
│   │   │   │   ├── UserAuthTokenRepository.php
│   │   │   │   ├── UserPasskeyRepository.php
│   │   │   │   └── UserRepository.php
│   │   │   ├── Requests
│   │   │   │   ├── README.md
│   │   │   │   └── UserRequest.php
│   │   │   ├── Responses
│   │   │   │   ├── README.md
│   │   │   │   └── UserResponse.php
│   │   │   ├── Routes
│   │   │   │   ├── README.md
│   │   │   │   └── web.php
│   │   │   ├── Seeds
│   │   │   │   ├── README.md
│   │   │   │   └── UserPlatformSeed.php
│   │   │   ├── Services
│   │   │   │   ├── README.md
│   │   │   │   ├── UserAuthService.php
│   │   │   │   ├── UserPasskeyService.php
│   │   │   │   └── UserProfileService.php
│   │   │   └── Views
│   │   │       ├── README.md
│   │   │       └── UserView.php
│   │   └── WebModule
│   │       ├── Controllers
│   │       │   ├── HomeController.php
│   │       │   └── README.md
│   │       ├── Middlewares
│   │       │   └── README.md
│   │       ├── Migrations
│   │       │   ├── CreatePagesTable.php
│   │       │   └── README.md
│   │       ├── Models
│   │       │   ├── Page.php
│   │       │   └── README.md
│   │       ├── Presenters
│   │       │   ├── PagePresenter.php
│   │       │   └── README.md
│   │       ├── Repositories
│   │       │   ├── PageRepository.php
│   │       │   └── README.md
│   │       ├── Requests
│   │       │   ├── README.md
│   │       │   └── WebRequest.php
│   │       ├── Responses
│   │       │   ├── README.md
│   │       │   └── WebResponse.php
│   │       ├── Routes
│   │       │   ├── README.md
│   │       │   └── web.php
│   │       ├── Seeds
│   │       │   ├── PageSeed.php
│   │       │   └── README.md
│   │       ├── Services
│   │       │   ├── PageService.php
│   │       │   └── README.md
│   │       └── Views
│   │           ├── README.md
│   │           └── WebView.php
│   ├── Providers
│   │   ├── CacheProvider.php
│   │   ├── CoreProvider.php
│   │   ├── CryptoProvider.php
│   │   ├── ExceptionProvider.php
│   │   ├── ModuleProvider.php
│   │   ├── NotificationProvider.php
│   │   ├── PaymentProvider.php
│   │   ├── QueueProvider.php
│   │   └── ShippingProvider.php
│   ├── Resources
│   │   ├── css
│   │   │   ├── langelermvc-theme.css
│   │   │   └── README.md
│   │   ├── images
│   │   │   └── README.md
│   │   └── js
│   │       ├── langelermvc-theme.js
│   │       └── README.md
│   ├── Support
│   │   ├── Commerce
│   │   │   ├── CartPricingManager.php
│   │   │   ├── CatalogLifecycleManager.php
│   │   │   ├── CommerceTotalsCalculator.php
│   │   │   ├── EntitlementManager.php
│   │   │   ├── InventoryManager.php
│   │   │   ├── OrderDocumentManager.php
│   │   │   ├── OrderLifecycleManager.php
│   │   │   ├── OrderReturnManager.php
│   │   │   ├── PromotionManager.php
│   │   │   ├── ShippingManager.php
│   │   │   └── SubscriptionManager.php
│   │   ├── Payments
│   │   │   ├── PaymentFlow.php
│   │   │   ├── PaymentIntent.php
│   │   │   ├── PaymentMethod.php
│   │   │   └── PaymentResult.php
│   │   ├── Theming
│   │   │   └── ThemeManager.php
│   │   └── ArrayMailable.php
│   ├── Templates
│   │   ├── Components
│   │   │   ├── BadgeList.lmv
│   │   │   ├── BadgeList.php
│   │   │   ├── BadgeList.vide
│   │   │   ├── CodeList.lmv
│   │   │   ├── CodeList.php
│   │   │   ├── CodeList.vide
│   │   │   ├── DataTable.lmv
│   │   │   ├── DataTable.php
│   │   │   ├── DataTable.vide
│   │   │   ├── DefinitionGrid.lmv
│   │   │   ├── DefinitionGrid.php
│   │   │   ├── DefinitionGrid.vide
│   │   │   ├── LinkList.lmv
│   │   │   ├── LinkList.php
│   │   │   ├── LinkList.vide
│   │   │   ├── ProductGrid.lmv
│   │   │   ├── ProductGrid.php
│   │   │   ├── ProductGrid.vide
│   │   │   └── README.md
│   │   ├── Layouts
│   │   │   ├── AdminShell.lmv
│   │   │   ├── AdminShell.php
│   │   │   ├── AdminShell.vide
│   │   │   ├── InstallerShell.lmv
│   │   │   ├── InstallerShell.vide
│   │   │   ├── UserShell.lmv
│   │   │   ├── UserShell.php
│   │   │   ├── UserShell.vide
│   │   │   ├── WebShell.lmv
│   │   │   ├── WebShell.php
│   │   │   └── WebShell.vide
│   │   ├── Pages
│   │   │   ├── AdminCarts.lmv
│   │   │   ├── AdminCarts.php
│   │   │   ├── AdminCarts.vide
│   │   │   ├── AdminCatalog.lmv
│   │   │   ├── AdminCatalog.php
│   │   │   ├── AdminCatalog.vide
│   │   │   ├── AdminDashboard.lmv
│   │   │   ├── AdminDashboard.php
│   │   │   ├── AdminDashboard.vide
│   │   │   ├── AdminOperations.lmv
│   │   │   ├── AdminOperations.php
│   │   │   ├── AdminOperations.vide
│   │   │   ├── AdminOrders.lmv
│   │   │   ├── AdminOrders.php
│   │   │   ├── AdminOrders.vide
│   │   │   ├── AdminPages.php
│   │   │   ├── AdminPages.vide
│   │   │   ├── AdminPromotions.php
│   │   │   ├── AdminPromotions.vide
│   │   │   ├── AdminRoles.lmv
│   │   │   ├── AdminRoles.php
│   │   │   ├── AdminRoles.vide
│   │   │   ├── AdminSystem.lmv
│   │   │   ├── AdminSystem.php
│   │   │   ├── AdminSystem.vide
│   │   │   ├── AdminUsers.lmv
│   │   │   ├── AdminUsers.php
│   │   │   ├── AdminUsers.vide
│   │   │   ├── CartPage.lmv
│   │   │   ├── CartPage.php
│   │   │   ├── CartPage.vide
│   │   │   ├── Home.lmv
│   │   │   ├── Home.php
│   │   │   ├── Home.vide
│   │   │   ├── InstallerWizard.lmv
│   │   │   ├── InstallerWizard.vide
│   │   │   ├── NotFound.lmv
│   │   │   ├── NotFound.php
│   │   │   ├── NotFound.vide
│   │   │   ├── OrderCheckout.lmv
│   │   │   ├── OrderCheckout.php
│   │   │   ├── OrderCheckout.vide
│   │   │   ├── OrderDetail.lmv
│   │   │   ├── OrderDetail.php
│   │   │   ├── OrderDetail.vide
│   │   │   ├── OrderEntitlement.php
│   │   │   ├── OrderEntitlement.vide
│   │   │   ├── OrderList.lmv
│   │   │   ├── OrderList.php
│   │   │   ├── OrderList.vide
│   │   │   ├── ShopCatalog.lmv
│   │   │   ├── ShopCatalog.php
│   │   │   ├── ShopCatalog.vide
│   │   │   ├── ShopProduct.lmv
│   │   │   ├── ShopProduct.php
│   │   │   ├── ShopProduct.vide
│   │   │   ├── UserLogin.lmv
│   │   │   ├── UserLogin.php
│   │   │   ├── UserLogin.vide
│   │   │   ├── UserPasswordForgot.lmv
│   │   │   ├── UserPasswordForgot.php
│   │   │   ├── UserPasswordForgot.vide
│   │   │   ├── UserPasswordReset.lmv
│   │   │   ├── UserPasswordReset.php
│   │   │   ├── UserPasswordReset.vide
│   │   │   ├── UserProfile.lmv
│   │   │   ├── UserProfile.php
│   │   │   ├── UserProfile.vide
│   │   │   ├── UserRegister.lmv
│   │   │   ├── UserRegister.php
│   │   │   ├── UserRegister.vide
│   │   │   ├── UserStatus.lmv
│   │   │   ├── UserStatus.php
│   │   │   └── UserStatus.vide
│   │   ├── Partials
│   │   │   ├── PageIntro.lmv
│   │   │   ├── PageIntro.php
│   │   │   ├── PageIntro.vide
│   │   │   ├── PanelMeta.lmv
│   │   │   ├── PanelMeta.php
│   │   │   ├── PanelMeta.vide
│   │   │   ├── README.md
│   │   │   ├── StatusMessage.lmv
│   │   │   ├── StatusMessage.php
│   │   │   └── StatusMessage.vide
│   │   └── README.md
│   └── Utilities
│       ├── Finders
│       │   ├── DirectoryFinder.php
│       │   └── FileFinder.php
│       ├── Handlers
│       │   ├── CryptoHandler.php
│       │   ├── DataHandler.php
│       │   ├── DataStructureHandler.php
│       │   ├── LocaleHandler.php
│       │   ├── MessageFormatterHandler.php
│       │   ├── NamespaceResolveHandler.php
│       │   ├── NormalizeHandler.php
│       │   ├── NumberFormatterHandler.php
│       │   ├── SQLHandler.php
│       │   └── SystemHandler.php
│       ├── Managers
│       │   ├── Async
│       │   │   ├── DatabaseFailedJobStore.php
│       │   │   ├── EventDispatcher.php
│       │   │   └── QueueManager.php
│       │   ├── Data
│       │   │   ├── CacheManager.php
│       │   │   ├── CryptoManager.php
│       │   │   ├── ModuleManager.php
│       │   │   └── SessionManager.php
│       │   ├── Commerce
│       │   │   ├── CartPricingManager.php
│       │   │   ├── CatalogLifecycleManager.php
│       │   │   ├── CommerceTotalsCalculator.php
│       │   │   ├── EntitlementManager.php
│       │   │   ├── InventoryManager.php
│       │   │   ├── OrderDocumentManager.php
│       │   │   ├── OrderLifecycleManager.php
│       │   │   ├── OrderReturnManager.php
│       │   │   ├── PromotionManager.php
│       │   │   ├── README.md
│       │   │   ├── ShippingManager.php
│       │   │   └── SubscriptionManager.php
│       │   ├── Presentation
│       │   │   ├── AssetManager.php
│       │   │   ├── HtmlManager.php
│       │   │   ├── README.md
│       │   │   ├── TemplateEngine.php
│       │   │   └── ThemeManager.php
│       │   ├── Security
│       │   │   ├── AuthManager.php
│       │   │   ├── DatabaseUserProvider.php
│       │   │   ├── Gate.php
│       │   │   ├── HttpSecurityManager.php
│       │   │   ├── PasswordBroker.php
│       │   │   ├── PermissionRegistry.php
│       │   │   ├── PolicyResolver.php
│       │   │   └── SessionGuard.php
│       │   ├── Support
│       │   │   ├── ArchitectureAlignmentManager.php
│       │   │   ├── AuditLogger.php
│       │   │   ├── FrameworkDoctor.php
│       │   │   ├── FrameworkLayerManager.php
│       │   │   ├── HealthManager.php
│       │   │   ├── MailManager.php
│       │   │   ├── NotificationManager.php
│       │   │   ├── OtpManager.php
│       │   │   ├── PasskeyManager.php
│       │   │   └── PaymentManager.php
│       │   ├── System
│       │   │   ├── CompressionManager.php
│       │   │   ├── DateTimeManager.php
│       │   │   ├── ErrorManager.php
│       │   │   ├── FileManager.php
│       │   │   ├── IteratorManager.php
│       │   │   ├── ReflectionManager.php
│       │   │   └── SettingsManager.php
│       │   ├── CacheManager.php
│       │   ├── CompressionManager.php
│       │   ├── DateTimeManager.php
│       │   ├── FileManager.php
│       │   ├── IteratorManager.php
│       │   ├── README.md
│       │   ├── ReflectionManager.php
│       │   ├── SessionManager.php
│       │   └── SettingsManager.php
│       ├── Query
│       │   ├── DataQuery.php
│       │   └── SchemaQuery.php
│       ├── Sanitation
│       │   ├── GeneralSanitizer.php
│       │   └── PatternSanitizer.php
│       ├── Traits
│       │   ├── Criteria
│       │   │   ├── DirectoryCriteriaTrait.php
│       │   │   └── FileCriteriaTrait.php
│       │   ├── Filters
│       │   │   ├── FiltrationTrait.php
│       │   │   ├── SanitationFilterTrait.php
│       │   │   ├── SanitationTrait.php
│       │   │   ├── ValidationFilterTrait.php
│       │   │   └── ValidationTrait.php
│       │   ├── Iterator
│       │   │   ├── IteratorTrait.php
│       │   │   └── RecursiveIteratorTrait.php
│       │   ├── Patterns
│       │   │   ├── PatternTrait.php
│       │   │   ├── SanitationPatternTrait.php
│       │   │   └── ValidationPatternTrait.php
│       │   ├── Query
│       │   │   ├── DataQueryTrait.php
│       │   │   └── SchemaQueryTrait.php
│       │   ├── Reflection
│       │   │   ├── ReflectionAttributeTrait.php
│       │   │   ├── ReflectionClassTrait.php
│       │   │   ├── ReflectionConstantTrait.php
│       │   │   ├── ReflectionEnumTrait.php
│       │   │   ├── ReflectionExtensionTrait.php
│       │   │   ├── ReflectionFunctionTrait.php
│       │   │   ├── ReflectionGeneratorTrait.php
│       │   │   ├── ReflectionMethodTrait.php
│       │   │   ├── ReflectionParameterTrait.php
│       │   │   ├── ReflectionPropertyTrait.php
│       │   │   ├── ReflectionTrait.php
│       │   │   └── ReflectionTypeTrait.php
│       │   ├── Rules
│       │   │   ├── RulesTrait.php
│       │   │   └── RuleTrait.php
│       │   ├── Sort
│       │   │   ├── DirectorySortTrait.php
│       │   │   └── FileSortTrait.php
│       │   ├── ApplicationPathTrait.php
│       │   ├── ArrayTrait.php
│       │   ├── CheckerTrait.php
│       │   ├── ConversionTrait.php
│       │   ├── DateTimeTrait.php
│       │   ├── DirectoryCriteriaTrait.php
│       │   ├── DirectorySortTrait.php
│       │   ├── EncodingTrait.php
│       │   ├── ErrorTrait.php
│       │   ├── ExistenceCheckerTrait.php
│       │   ├── FileCriteriaTrait.php
│       │   ├── FileSortTrait.php
│       │   ├── HashingTrait.php
│       │   ├── LocaleTrait.php
│       │   ├── LocaleUtilityTrait.php
│       │   ├── LoopTrait.php
│       │   ├── ManipulationTrait.php
│       │   ├── MetricsTrait.php
│       │   ├── MoneyFormattingTrait.php
│       │   ├── RetrieverTrait.php
│       │   └── TypeCheckerTrait.php
│       └── Validation
│           ├── GeneralValidator.php
│           └── PatternValidator.php
├── bootstrap
│   ├── app.php
│   └── console.php
├── Config
│   ├── app.php
│   ├── auth.php
│   ├── cache.php
│   ├── commerce.php
│   ├── cookie.php
│   ├── db.php
│   ├── encryption.php
│   ├── feature.php
│   ├── http.php
│   ├── mail.php
│   ├── notifications.php
│   ├── operations.php
│   ├── payment.php
│   ├── queue.php
│   ├── session.php
│   ├── theme.php
│   └── webmodule.php
├── Data
│   ├── Carts.sql
│   ├── Framework.sql
│   ├── Orders.sql
│   ├── Products.sql
│   ├── README.md
│   ├── Users.sql
│   └── Web.sql
├── Docs
│   ├── abstractcryptoclass.rtf
│   ├── ArchitectureOverview.md
│   ├── ArchitectureAlignment.md
│   ├── CompleteStructure.md
│   ├── DatabaseMatrixTesting.md
│   ├── DeploymentAndUpgrade.md
│   ├── FolderStructure.md
│   ├── FrameworkStatus.md
│   ├── InstallationWizard.md
│   ├── IteratorManager.md
│   ├── IteratorManager Usage.pdf
│   ├── IteratorManager Usage.rtf
│   ├── ModulesStructure.md
│   ├── NativeToTraitConsistencyAudit.md
│   ├── opensslcryptoclass.rtf
│   ├── OperationsGuide.md
│   ├── PaymentDrivers.md
│   ├── FrameworkWideLayerEvaluation.md
│   ├── PresentationLayerEvaluation.md
│   ├── PresentationTemplating.md
│   ├── README.md
│   ├── ReleaseReadinessPlan.md
│   ├── RepositoryMetadata.md
│   ├── SanitationValidationAPI.md
│   ├── ShippingAdapters.md
│   ├── ThemeManagement.md
│   ├── sodiumcryptoclass.rtf
│   ├── Untitled 5.rtf
│   ├── Untitled 6.rtf
│   ├── UtilitiesTraitsOverview.md
│   ├── UtilitiesTraitsReference.md
│   └── Wiki
│       ├── Commerce-And-Providers.md
│       ├── Documentation-Index.md
│       ├── Home.md
│       ├── Installation.md
│       ├── Operations.md
│       └── Release-Status.md
├── Public
│   ├── assets
│   │   ├── css
│   │   │   ├── langelermvc-theme.css
│   │   │   └── README.md
│   │   ├── images
│   │   │   ├── admin-operations-pack.svg
│   │   │   ├── queue-visibility-dashboard.svg
│   │   │   ├── README.md
│   │   │   └── starter-platform-license.svg
│   │   └── js
│   │       ├── langelermvc-theme.js
│   │       └── README.md
│   ├── install
│   │   └── index.php
│   ├── .htaccess
│   └── index.php
├── Scripts
│   ├── AuditNativeToTraitConsistency.pl
│   └── GenerateUtilitiesTraitsReference.pl
├── Services
│   └── README.md
├── Storage
│   ├── Logs
│   │   └── README.md
│   ├── Secure
│   │   └── README.md
│   ├── Sessions
│   │   └── README.md
│   └── Uploads
│       └── README.md
├── Tests
│   ├── DbMatrix
│   │   ├── DatabaseMatrixHarnessTest.php
│   │   └── RuntimeBackendHarnessTest.php
│   ├── Framework
│   │   ├── AdapterCompatibilityTest.php
│   │   ├── AsyncOperationsHardeningTest.php
│   │   ├── AuthPlatformTest.php
│   │   ├── BackendArchitectureTest.php
│   │   ├── BootstrapAndAppTest.php
│   │   ├── CacheSubsystemTest.php
│   │   ├── ConfigAndDatabaseTest.php
│   │   ├── CryptoSubsystemTest.php
│   │   ├── ArchitectureAlignmentManagerTest.php
│   │   ├── FinderUtilitiesAndSessionTest.php
│   │   ├── FrameworkCompletionTest.php
│   │   ├── FrameworkDoctorTest.php
│   │   ├── FrameworkLayerManagerTest.php
│   │   ├── HttpPresentationSurfaceTest.php
│   │   ├── HttpSecurityEnforcementTest.php
│   │   ├── InfrastructureHardeningTest.php
│   │   ├── InstallerAndViewCoverageTest.php
│   │   ├── ModelAndRepositoryTest.php
│   │   ├── MvcLayerTest.php
│   │   ├── OperationsMaintenanceTest.php
│   │   ├── PlatformFoundationTest.php
│   │   ├── PresentationLayerCompletionTest.php
│   │   ├── QueryLayerTest.php
│   │   ├── ReleaseReadinessTest.php
│   │   ├── RepositoryConsistencyTest.php
│   │   ├── RouterTest.php
│   │   ├── SessionSubsystemTest.php
│   │   ├── ThemeManagementTest.php
│   │   ├── TraitSurfaceTest.php
│   │   ├── UtilityLayerHardeningTest.php
│   │   └── ValidationAndSanitizationTest.php
│   ├── Integration
│   │   └── README.md
│   └── Unit
│       └── README.md
├── .env.example
├── .gitignore
├── autoload.php
├── CHANGELOG.md
├── composer.json
├── composer.lock
├── console
├── CONTRIBUTING.md
├── docker-compose.verify.yml
├── logo.jpeg
├── phpunit.db-matrix.xml
├── phpunit.xml
├── readme.md
├── RELEASE.md
└── SECURITY.md
```
