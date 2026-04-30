# Complete Structure

This indexed tree reflects the current repository structure and excludes only `.git` and `vendor`.

Placeholder `README.md` files that remain in repeated architecture folders are intentional and help keep the full framework shape visible in the repository.

Inside `App/Templates`, the tree highlights the canonical native `.vide` templates. Compatibility `.lmv` and `.php` counterparts are intentionally omitted there to keep the presentation surface readable.

```text
LangelerMVC
├── .github
│   ├── workflows
│   │   ├── php.yml
├── CONTRIBUTING.md
├── .env
├── .env.example
├── .gitignore
├── .nova
│   ├── Configuration.json
├── App
│   ├── Abstracts
│   │   ├── Console
│   │   │   ├── Command.php
│   │   ├── Data
│   │   │   ├── Cache.php
│   │   │   ├── Crypto.php
│   │   │   ├── Finder.php
│   │   │   ├── Sanitizer.php
│   │   │   ├── SchemaProcessor.php
│   │   │   ├── Validator.php
│   │   ├── Database
│   │   │   ├── Migration.php
│   │   │   ├── Model.php
│   │   │   ├── Query.php
│   │   │   ├── Repository.php
│   │   │   ├── Seed.php
│   │   ├── Http
│   │   │   ├── Controller.php
│   │   │   ├── InboundRequest.php
│   │   │   ├── Middleware.php
│   │   │   ├── Request.php
│   │   │   ├── Response.php
│   │   │   ├── Service.php
│   │   │   ├── StandardResponse.php
│   │   ├── Presentation
│   │   │   ├── Presenter.php
│   │   │   ├── Resource.php
│   │   │   ├── ResourceCollection.php
│   │   │   ├── View.php
│   │   ├── Support
│   │   │   ├── CarrierAdapter.php
│   │   │   ├── Mailable.php
│   │   │   ├── Notification.php
│   │   │   ├── PaymentDriver.php
│   ├── Console
│   │   ├── Commands
│   │   │   ├── AuditListCommand.php
│   │   │   ├── AuditPruneCommand.php
│   │   │   ├── CacheClearCommand.php
│   │   │   ├── ConfigShowCommand.php
│   │   │   ├── EventListCommand.php
│   │   │   ├── FrameworkDoctorCommand.php
│   │   │   ├── HealthCheckCommand.php
│   │   │   ├── MigrateCommand.php
│   │   │   ├── MigrateRollbackCommand.php
│   │   │   ├── MigrateStatusCommand.php
│   │   │   ├── ModuleMakeCommand.php
│   │   │   ├── ModuleListCommand.php
│   │   │   ├── NotificationListCommand.php
│   │   │   ├── QueueDrainCommand.php
│   │   │   ├── QueueFailedCommand.php
│   │   │   ├── QueuePruneFailedCommand.php
│   │   │   ├── QueueRetryCommand.php
│   │   │   ├── QueueStopCommand.php
│   │   │   ├── QueueWorkCommand.php
│   │   │   ├── ReleaseCheckCommand.php
│   │   │   ├── RouteListCommand.php
│   │   │   ├── SeedCommand.php
│   │   ├── ConsoleKernel.php
│   ├── Contracts
│   │   ├── Async
│   │   │   ├── EventDispatcherInterface.php
│   │   │   ├── FailedJobStoreInterface.php
│   │   │   ├── JobInterface.php
│   │   │   ├── ListenerInterface.php
│   │   │   ├── QueueDriverInterface.php
│   │   ├── Auth
│   │   │   ├── AuthenticatableInterface.php
│   │   │   ├── GuardInterface.php
│   │   │   ├── PasswordBrokerInterface.php
│   │   │   ├── UserProviderInterface.php
│   │   ├── Console
│   │   │   ├── CommandInterface.php
│   │   ├── Data
│   │   │   ├── CacheDriverInterface.php
│   │   │   ├── CryptoInterface.php
│   │   │   ├── FinderInterface.php
│   │   │   ├── SanitizerInterface.php
│   │   │   ├── ValidatorInterface.php
│   │   ├── Database
│   │   │   ├── MigrationInterface.php
│   │   │   ├── ModelInterface.php
│   │   │   ├── RepositoryInterface.php
│   │   │   ├── SeedInterface.php
│   │   ├── Http
│   │   │   ├── ControllerInterface.php
│   │   │   ├── MiddlewareInterface.php
│   │   │   ├── RequestInterface.php
│   │   │   ├── ResponseInterface.php
│   │   │   ├── ServiceInterface.php
│   │   ├── Presentation
│   │   │   ├── PresenterInterface.php
│   │   │   ├── ResourceInterface.php
│   │   │   ├── ViewInterface.php
│   │   ├── Session
│   │   │   ├── SessionDriverInterface.php
│   │   ├── Support
│   │   │   ├── AuditLoggerInterface.php
│   │   │   ├── CarrierAdapterInterface.php
│   │   │   ├── HealthManagerInterface.php
│   │   │   ├── MailerInterface.php
│   │   │   ├── NotifiableInterface.php
│   │   │   ├── NotificationChannelInterface.php
│   │   │   ├── NotificationInterface.php
│   │   │   ├── NotificationManagerInterface.php
│   │   │   ├── OtpManagerInterface.php
│   │   │   ├── PasskeyDriverInterface.php
│   │   │   ├── PasskeyManagerInterface.php
│   │   │   ├── PaymentDriverInterface.php
│   │   │   ├── PaymentManagerInterface.php
│   ├── Core
│   │   ├── App.php
│   │   ├── Bootstrap.php
│   │   ├── Config.php
│   │   ├── Container.php
│   │   ├── Database.php
│   │   ├── MigrationRunner.php
│   │   ├── ModuleManager.php
│   │   ├── Router.php
│   │   ├── Schema
│   │   │   ├── Blueprint.php
│   │   ├── SeedRunner.php
│   │   ├── Session.php
│   ├── Drivers
│   │   ├── Caching
│   │   │   ├── ArrayCache.php
│   │   │   ├── DatabaseCache.php
│   │   │   ├── FileCache.php
│   │   │   ├── MemCache.php
│   │   │   ├── RedisCache.php
│   │   ├── Cryptography
│   │   │   ├── OpenSSLCrypto.php
│   │   │   ├── SodiumCrypto.php
│   │   ├── Notifications
│   │   │   ├── DatabaseNotificationChannel.php
│   │   │   ├── MailNotificationChannel.php
│   │   ├── Passkeys
│   │   │   ├── TestingPasskeyDriver.php
│   │   │   ├── WebAuthnPasskeyDriver.php
│   │   ├── Payments
│   │   │   ├── CardPaymentDriver.php
│   │   │   ├── CryptoPaymentDriver.php
│   │   │   ├── KlarnaPaymentDriver.php
│   │   │   ├── PayPalPaymentDriver.php
│   │   │   ├── QliroPaymentDriver.php
│   │   │   ├── SwishPaymentDriver.php
│   │   │   ├── TestingPaymentDriver.php
│   │   │   ├── WalleyPaymentDriver.php
│   │   ├── Shipping
│   │   │   ├── AirmeeCarrierAdapter.php
│   │   │   ├── BringCarrierAdapter.php
│   │   │   ├── BudbeeCarrierAdapter.php
│   │   │   ├── DhlCarrierAdapter.php
│   │   │   ├── EarlyBirdCarrierAdapter.php
│   │   │   ├── InstaboxCarrierAdapter.php
│   │   │   ├── PostNordCarrierAdapter.php
│   │   │   ├── SchenkerCarrierAdapter.php
│   │   │   ├── UpsCarrierAdapter.php
│   │   ├── Queue
│   │   │   ├── DatabaseQueueDriver.php
│   │   │   ├── SyncQueueDriver.php
│   │   ├── Session
│   │   │   ├── DatabaseSessionDriver.php
│   │   │   ├── EncryptedSessionDriver.php
│   │   │   ├── FileSessionDriver.php
│   │   │   ├── README.md
│   │   │   ├── RedisSessionDriver.php
│   ├── Exceptions
│   │   ├── AppException.php
│   │   ├── AuthException.php
│   │   ├── ConfigException.php
│   │   ├── ContainerException.php
│   │   ├── Data
│   │   │   ├── CacheException.php
│   │   │   ├── CryptoException.php
│   │   │   ├── FinderException.php
│   │   │   ├── SanitizationException.php
│   │   │   ├── ValidationException.php
│   │   ├── Database
│   │   │   ├── DatabaseException.php
│   │   │   ├── MigrationException.php
│   │   │   ├── ModelException.php
│   │   │   ├── RepositoryException.php
│   │   │   ├── SeedException.php
│   │   ├── Http
│   │   │   ├── ControllerException.php
│   │   │   ├── MiddlewareException.php
│   │   │   ├── RequestException.php
│   │   │   ├── ResponseException.php
│   │   │   ├── ServiceException.php
│   │   ├── Iterator
│   │   │   ├── IteratorException.php
│   │   │   ├── IteratorNotFoundException.php
│   │   ├── Presentation
│   │   │   ├── PresenterException.php
│   │   │   ├── ViewException.php
│   │   ├── RouteNotFoundException.php
│   │   ├── RouterException.php
│   │   ├── SessionException.php
│   │   ├── Support
│   │   │   ├── PaymentException.php
│   ├── Helpers
│   │   ├── README.md
│   ├── Modules
│   │   ├── AdminModule
│   │   │   ├── Controllers
│   │   │   │   ├── AdminController.php
│   │   │   │   ├── README.md
│   │   │   ├── Middlewares
│   │   │   │   ├── AdminAccessMiddleware.php
│   │   │   │   ├── README.md
│   │   │   ├── Migrations
│   │   │   │   ├── README.md
│   │   │   ├── Models
│   │   │   │   ├── README.md
│   │   │   ├── Presenters
│   │   │   │   ├── AdminPresenter.php
│   │   │   │   ├── AdminResource.php
│   │   │   │   ├── README.md
│   │   │   ├── Repositories
│   │   │   │   ├── README.md
│   │   │   ├── Requests
│   │   │   │   ├── AdminRequest.php
│   │   │   │   ├── README.md
│   │   │   ├── Responses
│   │   │   │   ├── AdminResponse.php
│   │   │   │   ├── README.md
│   │   │   ├── Routes
│   │   │   │   ├── README.md
│   │   │   │   ├── web.php
│   │   │   ├── Seeds
│   │   │   │   ├── README.md
│   │   │   ├── Services
│   │   │   │   ├── AdminAccessService.php
│   │   │   │   ├── README.md
│   │   │   ├── Views
│   │   │   │   ├── AdminView.php
│   │   │   │   ├── README.md
│   │   ├── CartModule
│   │   │   ├── Controllers
│   │   │   │   ├── CartController.php
│   │   │   │   ├── README.md
│   │   │   ├── Listeners
│   │   │   │   ├── MergeCartOnLoginListener.php
│   │   │   ├── Middlewares
│   │   │   │   ├── README.md
│   │   │   ├── Migrations
│   │   │   │   ├── CreateCartTables.php
│   │   │   │   ├── README.md
│   │   │   ├── Models
│   │   │   │   ├── Cart.php
│   │   │   │   ├── CartItem.php
│   │   │   │   ├── README.md
│   │   │   ├── Notifications
│   │   │   ├── Presenters
│   │   │   │   ├── CartPresenter.php
│   │   │   │   ├── CartResource.php
│   │   │   │   ├── README.md
│   │   │   ├── Repositories
│   │   │   │   ├── CartItemRepository.php
│   │   │   │   ├── CartRepository.php
│   │   │   │   ├── README.md
│   │   │   ├── Requests
│   │   │   │   ├── CartRequest.php
│   │   │   │   ├── README.md
│   │   │   ├── Responses
│   │   │   │   ├── CartResponse.php
│   │   │   │   ├── README.md
│   │   │   ├── Routes
│   │   │   │   ├── README.md
│   │   │   │   ├── web.php
│   │   │   ├── Seeds
│   │   │   │   ├── CartSeed.php
│   │   │   │   ├── README.md
│   │   │   ├── Services
│   │   │   │   ├── CartService.php
│   │   │   │   ├── README.md
│   │   │   ├── Views
│   │   │   │   ├── CartView.php
│   │   │   │   ├── README.md
│   │   ├── OrderModule
│   │   │   ├── Controllers
│   │   │   │   ├── OrderController.php
│   │   │   │   ├── README.md
│   │   │   ├── Listeners
│   │   │   │   ├── OrderLifecycleNotificationListener.php
│   │   │   ├── Middlewares
│   │   │   │   ├── README.md
│   │   │   ├── Migrations
│   │   │   │   ├── AddOrderCommerceStateColumns.php
│   │   │   │   ├── AddOrderDiscountSnapshotColumns.php
│   │   │   │   ├── AddOrderShipmentTrackingColumns.php
│   │   │   │   ├── CreateOrderEntitlementsTable.php
│   │   │   │   ├── CreateOrderTables.php
│   │   │   │   ├── CreatePaymentWebhookEventsTable.php
│   │   │   │   ├── README.md
│   │   │   ├── Models
│   │   │   │   ├── Order.php
│   │   │   │   ├── OrderAddress.php
│   │   │   │   ├── OrderEntitlement.php
│   │   │   │   ├── OrderItem.php
│   │   │   │   ├── PaymentWebhookEvent.php
│   │   │   │   ├── README.md
│   │   │   ├── Notifications
│   │   │   │   ├── OrderStatusNotification.php
│   │   │   ├── Presenters
│   │   │   │   ├── OrderPresenter.php
│   │   │   │   ├── OrderResource.php
│   │   │   │   ├── README.md
│   │   │   ├── Repositories
│   │   │   │   ├── OrderAddressRepository.php
│   │   │   │   ├── OrderEntitlementRepository.php
│   │   │   │   ├── OrderItemRepository.php
│   │   │   │   ├── OrderRepository.php
│   │   │   │   ├── PaymentWebhookEventRepository.php
│   │   │   │   ├── README.md
│   │   │   ├── Requests
│   │   │   │   ├── OrderRequest.php
│   │   │   │   ├── README.md
│   │   │   ├── Responses
│   │   │   │   ├── OrderResponse.php
│   │   │   │   ├── README.md
│   │   │   ├── Routes
│   │   │   │   ├── README.md
│   │   │   │   ├── web.php
│   │   │   ├── Seeds
│   │   │   │   ├── OrderSeed.php
│   │   │   │   ├── README.md
│   │   │   ├── Services
│   │   │   │   ├── OrderService.php
│   │   │   │   ├── README.md
│   │   │   ├── Views
│   │   │   │   ├── OrderView.php
│   │   │   │   ├── README.md
│   │   ├── ShopModule
│   │   │   ├── Controllers
│   │   │   │   ├── README.md
│   │   │   │   ├── ShopController.php
│   │   │   ├── Listeners
│   │   │   ├── Middlewares
│   │   │   │   ├── README.md
│   │   │   ├── Migrations
│   │   │   │   ├── CreateShopTables.php
│   │   │   │   ├── README.md
│   │   │   ├── Models
│   │   │   │   ├── Category.php
│   │   │   │   ├── Product.php
│   │   │   │   ├── README.md
│   │   │   ├── Notifications
│   │   │   ├── Presenters
│   │   │   │   ├── README.md
│   │   │   │   ├── ShopPresenter.php
│   │   │   │   ├── ShopResource.php
│   │   │   ├── Repositories
│   │   │   │   ├── CategoryRepository.php
│   │   │   │   ├── ProductRepository.php
│   │   │   │   ├── README.md
│   │   │   ├── Requests
│   │   │   │   ├── README.md
│   │   │   │   ├── ShopRequest.php
│   │   │   ├── Responses
│   │   │   │   ├── README.md
│   │   │   │   ├── ShopResponse.php
│   │   │   ├── Routes
│   │   │   │   ├── README.md
│   │   │   │   ├── web.php
│   │   │   ├── Seeds
│   │   │   │   ├── README.md
│   │   │   │   ├── ShopSeed.php
│   │   │   ├── Services
│   │   │   │   ├── CatalogService.php
│   │   │   │   ├── README.md
│   │   │   ├── Views
│   │   │   │   ├── README.md
│   │   │   │   ├── ShopView.php
│   │   ├── UserModule
│   │   │   ├── Controllers
│   │   │   │   ├── AuthController.php
│   │   │   │   ├── PasskeyController.php
│   │   │   │   ├── ProfileController.php
│   │   │   │   ├── README.md
│   │   │   ├── Middlewares
│   │   │   │   ├── AuthenticateMiddleware.php
│   │   │   │   ├── README.md
│   │   │   ├── Migrations
│   │   │   │   ├── CreateUserPlatformTables.php
│   │   │   │   ├── README.md
│   │   │   ├── Models
│   │   │   │   ├── Permission.php
│   │   │   │   ├── README.md
│   │   │   │   ├── Role.php
│   │   │   │   ├── User.php
│   │   │   │   ├── UserAuthToken.php
│   │   │   │   ├── UserPasskey.php
│   │   │   ├── Presenters
│   │   │   │   ├── README.md
│   │   │   │   ├── UserPresenter.php
│   │   │   │   ├── UserResource.php
│   │   │   ├── Repositories
│   │   │   │   ├── PermissionRepository.php
│   │   │   │   ├── README.md
│   │   │   │   ├── RoleRepository.php
│   │   │   │   ├── UserAuthTokenRepository.php
│   │   │   │   ├── UserPasskeyRepository.php
│   │   │   │   ├── UserRepository.php
│   │   │   ├── Requests
│   │   │   │   ├── README.md
│   │   │   │   ├── UserRequest.php
│   │   │   ├── Responses
│   │   │   │   ├── README.md
│   │   │   │   ├── UserResponse.php
│   │   │   ├── Routes
│   │   │   │   ├── README.md
│   │   │   │   ├── web.php
│   │   │   ├── Seeds
│   │   │   │   ├── README.md
│   │   │   │   ├── UserPlatformSeed.php
│   │   │   ├── Services
│   │   │   │   ├── README.md
│   │   │   │   ├── UserAuthService.php
│   │   │   │   ├── UserPasskeyService.php
│   │   │   │   ├── UserProfileService.php
│   │   │   ├── Views
│   │   │   │   ├── README.md
│   │   │   │   ├── UserView.php
│   │   ├── WebModule
│   │   │   ├── Controllers
│   │   │   │   ├── HomeController.php
│   │   │   ├── Middlewares
│   │   │   │   ├── README.md
│   │   │   ├── Migrations
│   │   │   │   ├── CreatePagesTable.php
│   │   │   │   ├── README.md
│   │   │   ├── Models
│   │   │   │   ├── Page.php
│   │   │   ├── Presenters
│   │   │   │   ├── PagePresenter.php
│   │   │   ├── Repositories
│   │   │   │   ├── PageRepository.php
│   │   │   ├── Requests
│   │   │   │   ├── WebRequest.php
│   │   │   ├── Responses
│   │   │   │   ├── WebResponse.php
│   │   │   ├── Routes
│   │   │   │   ├── web.php
│   │   │   ├── Seeds
│   │   │   │   ├── PageSeed.php
│   │   │   │   ├── README.md
│   │   │   ├── Services
│   │   │   │   ├── PageService.php
│   │   │   ├── Views
│   │   │   │   ├── WebView.php
│   ├── Providers
│   │   ├── CacheProvider.php
│   │   ├── CoreProvider.php
│   │   ├── CryptoProvider.php
│   │   ├── ExceptionProvider.php
│   │   ├── ModuleProvider.php
│   │   ├── NotificationProvider.php
│   │   ├── PaymentProvider.php
│   │   ├── QueueProvider.php
│   │   ├── ShippingProvider.php
│   ├── Resources
│   │   ├── css
│   │   │   ├── README.md
│   │   ├── images
│   │   │   ├── README.md
│   │   ├── js
│   │   │   ├── README.md
│   ├── Support
│   │   ├── ArrayMailable.php
│   │   ├── Commerce
│   │   │   ├── ShippingManager.php
│   │   │   ├── SubscriptionManager.php
│   │   ├── Payments
│   │   │   ├── PaymentFlow.php
│   │   │   ├── PaymentIntent.php
│   │   │   ├── PaymentMethod.php
│   │   │   ├── PaymentResult.php
│   ├── Templates
│   │   ├── Components
│   │   │   ├── BadgeList.vide
│   │   │   ├── CodeList.vide
│   │   │   ├── DataTable.vide
│   │   │   ├── DefinitionGrid.vide
│   │   │   ├── LinkList.vide
│   │   │   ├── ProductGrid.vide
│   │   │   ├── README.md
│   │   ├── Layouts
│   │   │   ├── AdminShell.vide
│   │   │   ├── InstallerShell.vide
│   │   │   ├── UserShell.vide
│   │   │   ├── WebShell.vide
│   │   ├── Pages
│   │   │   ├── AdminCarts.vide
│   │   │   ├── AdminCatalog.vide
│   │   │   ├── AdminDashboard.vide
│   │   │   ├── AdminOperations.vide
│   │   │   ├── AdminOrders.vide
│   │   │   ├── AdminRoles.vide
│   │   │   ├── AdminSystem.vide
│   │   │   ├── AdminUsers.vide
│   │   │   ├── CartPage.vide
│   │   │   ├── Home.vide
│   │   │   ├── InstallerWizard.vide
│   │   │   ├── NotFound.vide
│   │   │   ├── OrderCheckout.vide
│   │   │   ├── OrderDetail.vide
│   │   │   ├── OrderList.vide
│   │   │   ├── ShopCatalog.vide
│   │   │   ├── ShopProduct.vide
│   │   │   ├── UserLogin.vide
│   │   │   ├── UserPasswordForgot.vide
│   │   │   ├── UserPasswordReset.vide
│   │   │   ├── UserProfile.vide
│   │   │   ├── UserRegister.vide
│   │   │   ├── UserStatus.vide
│   │   ├── Partials
│   │   │   ├── PageIntro.vide
│   │   │   ├── PanelMeta.vide
│   │   │   ├── README.md
│   │   │   ├── StatusMessage.vide
│   ├── Utilities
│   │   ├── Finders
│   │   │   ├── DirectoryFinder.php
│   │   │   ├── FileFinder.php
│   │   ├── Handlers
│   │   │   ├── CryptoHandler.php
│   │   │   ├── DataHandler.php
│   │   │   ├── DataStructureHandler.php
│   │   │   ├── LocaleHandler.php
│   │   │   ├── MessageFormatterHandler.php
│   │   │   ├── NamespaceResolveHandler.php
│   │   │   ├── NormalizeHandler.php
│   │   │   ├── NumberFormatterHandler.php
│   │   │   ├── SQLHandler.php
│   │   │   ├── SystemHandler.php
│   │   ├── Managers
│   │   │   ├── Async
│   │   │   │   ├── DatabaseFailedJobStore.php
│   │   │   │   ├── EventDispatcher.php
│   │   │   │   ├── QueueManager.php
│   │   │   ├── CacheManager.php
│   │   │   ├── CompressionManager.php
│   │   │   ├── Data
│   │   │   │   ├── CacheManager.php
│   │   │   │   ├── CryptoManager.php
│   │   │   │   ├── ModuleManager.php
│   │   │   │   ├── SessionManager.php
│   │   │   ├── DateTimeManager.php
│   │   │   ├── FileManager.php
│   │   │   ├── IteratorManager.php
│   │   │   ├── ReflectionManager.php
│   │   │   ├── Security
│   │   │   │   ├── AuthManager.php
│   │   │   │   ├── DatabaseUserProvider.php
│   │   │   │   ├── Gate.php
│   │   │   │   ├── HttpSecurityManager.php
│   │   │   │   ├── PasswordBroker.php
│   │   │   │   ├── PermissionRegistry.php
│   │   │   │   ├── PolicyResolver.php
│   │   │   │   ├── SessionGuard.php
│   │   │   ├── SessionManager.php
│   │   │   ├── SettingsManager.php
│   │   │   ├── Support
│   │   │   │   ├── AuditLogger.php
│   │   │   │   ├── HealthManager.php
│   │   │   │   ├── MailManager.php
│   │   │   │   ├── NotificationManager.php
│   │   │   │   ├── OtpManager.php
│   │   │   │   ├── PasskeyManager.php
│   │   │   │   ├── PaymentManager.php
│   │   │   ├── System
│   │   │   │   ├── CompressionManager.php
│   │   │   │   ├── DateTimeManager.php
│   │   │   │   ├── ErrorManager.php
│   │   │   │   ├── FileManager.php
│   │   │   │   ├── IteratorManager.php
│   │   │   │   ├── ReflectionManager.php
│   │   │   │   ├── SettingsManager.php
│   │   ├── Query
│   │   │   ├── DataQuery.php
│   │   │   ├── SchemaQuery.php
│   │   ├── Sanitation
│   │   │   ├── GeneralSanitizer.php
│   │   │   ├── PatternSanitizer.php
│   │   ├── Traits
│   │   │   ├── ApplicationPathTrait.php
│   │   │   ├── ArrayTrait.php
│   │   │   ├── CheckerTrait.php
│   │   │   ├── ConversionTrait.php
│   │   │   ├── Criteria
│   │   │   │   ├── DirectoryCriteriaTrait.php
│   │   │   │   ├── FileCriteriaTrait.php
│   │   │   ├── DateTimeTrait.php
│   │   │   ├── DirectoryCriteriaTrait.php
│   │   │   ├── DirectorySortTrait.php
│   │   │   ├── EncodingTrait.php
│   │   │   ├── ErrorTrait.php
│   │   │   ├── ExistenceCheckerTrait.php
│   │   │   ├── FileCriteriaTrait.php
│   │   │   ├── FileSortTrait.php
│   │   │   ├── Filters
│   │   │   │   ├── FiltrationTrait.php
│   │   │   │   ├── SanitationFilterTrait.php
│   │   │   │   ├── SanitationTrait.php
│   │   │   │   ├── ValidationFilterTrait.php
│   │   │   │   ├── ValidationTrait.php
│   │   │   ├── HashingTrait.php
│   │   │   ├── Iterator
│   │   │   │   ├── IteratorTrait.php
│   │   │   │   ├── RecursiveIteratorTrait.php
│   │   │   ├── LocaleTrait.php
│   │   │   ├── LocaleUtilityTrait.php
│   │   │   ├── LoopTrait.php
│   │   │   ├── ManipulationTrait.php
│   │   │   ├── MetricsTrait.php
│   │   │   ├── Patterns
│   │   │   │   ├── PatternTrait.php
│   │   │   │   ├── SanitationPatternTrait.php
│   │   │   │   ├── ValidationPatternTrait.php
│   │   │   ├── Query
│   │   │   │   ├── DataQueryTrait.php
│   │   │   │   ├── SchemaQueryTrait.php
│   │   │   ├── Reflection
│   │   │   │   ├── ReflectionAttributeTrait.php
│   │   │   │   ├── ReflectionClassTrait.php
│   │   │   │   ├── ReflectionConstantTrait.php
│   │   │   │   ├── ReflectionEnumTrait.php
│   │   │   │   ├── ReflectionExtensionTrait.php
│   │   │   │   ├── ReflectionFunctionTrait.php
│   │   │   │   ├── ReflectionGeneratorTrait.php
│   │   │   │   ├── ReflectionMethodTrait.php
│   │   │   │   ├── ReflectionParameterTrait.php
│   │   │   │   ├── ReflectionPropertyTrait.php
│   │   │   │   ├── ReflectionTrait.php
│   │   │   │   ├── ReflectionTypeTrait.php
│   │   │   ├── RetrieverTrait.php
│   │   │   ├── Rules
│   │   │   │   ├── RuleTrait.php
│   │   │   │   ├── RulesTrait.php
│   │   │   ├── Sort
│   │   │   │   ├── DirectorySortTrait.php
│   │   │   │   ├── FileSortTrait.php
│   │   │   ├── TypeCheckerTrait.php
│   │   ├── Validation
│   │   │   ├── GeneralValidator.php
│   │   │   ├── PatternValidator.php
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
│   ├── payment.php
│   ├── queue.php
│   ├── session.php
│   ├── webmodule.php
├── Data
│   ├── Carts.sql
│   ├── Orders.sql
│   ├── Products.sql
│   ├── Users.sql
├── Docs
│   ├── ArchitectureOverview.md
│   ├── CompleteStructure.md
│   ├── DatabaseMatrixTesting.md
│   ├── DeploymentAndUpgrade.md
│   ├── FolderStructure.md
│   ├── FrameworkStatus.md
│   ├── IteratorManager Usage.pdf
│   ├── IteratorManager Usage.rtf
│   ├── IteratorManager.md
│   ├── ModulesStructure.md
│   ├── NativeToTraitConsistencyAudit.md
│   ├── OperationsGuide.md
│   ├── PaymentDrivers.md
│   ├── ShippingAdapters.md
│   ├── README.md
│   ├── SanitationValidationAPI.md
│   ├── Untitled 5.rtf
│   ├── Untitled 6.rtf
│   ├── UtilitiesTraitsOverview.md
│   ├── UtilitiesTraitsReference.md
│   ├── abstractcryptoclass.rtf
│   ├── opensslcryptoclass.rtf
│   ├── sodiumcryptoclass.rtf
├── Public
│   ├── .htaccess
│   ├── assets
│   │   ├── css
│   │   │   ├── README.md
│   │   ├── images
│   │   │   ├── admin-operations-pack.svg
│   │   │   ├── queue-visibility-dashboard.svg
│   │   │   ├── README.md
│   │   │   ├── starter-platform-license.svg
│   │   ├── js
│   │   │   ├── README.md
│   ├── index.php
├── Scripts
│   ├── AuditNativeToTraitConsistency.pl
│   ├── GenerateUtilitiesTraitsReference.pl
├── Services
│   ├── README.md
├── Storage
│   ├── Cache
│   ├── Logs
│   │   ├── README.md
│   ├── Secure
│   │   ├── README.md
│   ├── Sessions
│   │   ├── README.md
│   ├── Uploads
│   │   ├── README.md
├── Tests
│   ├── DbMatrix
│   │   ├── DatabaseMatrixHarnessTest.php
│   ├── Framework
│   │   ├── AsyncOperationsHardeningTest.php
│   │   ├── AuthPlatformTest.php
│   │   ├── BackendArchitectureTest.php
│   │   ├── BootstrapAndAppTest.php
│   │   ├── CacheSubsystemTest.php
│   │   ├── ConfigAndDatabaseTest.php
│   │   ├── CryptoSubsystemTest.php
│   │   ├── FinderUtilitiesAndSessionTest.php
│   │   ├── FrameworkCompletionTest.php
│   │   ├── FrameworkDoctorTest.php
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
│   │   ├── RouterTest.php
│   │   ├── SessionSubsystemTest.php
│   │   ├── TraitSurfaceTest.php
│   │   ├── UtilityLayerHardeningTest.php
│   │   ├── ValidationAndSanitizationTest.php
│   ├── Integration
│   │   ├── README.md
│   ├── Unit
│   │   ├── README.md
├── autoload.php
├── bootstrap
│   ├── app.php
│   ├── console.php
├── composer.json
├── composer.lock
├── console
├── docker-compose.verify.yml
├── logo.jpeg
├── phpunit.db-matrix.xml
├── phpunit.xml
├── readme.md
├── SECURITY.md
```
