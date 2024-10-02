LangelerMVC
├── .env
├── .env.example
├── .git
├── .gitignore
├── .nova
├── autoload.php
├── composer.json
├── composer.lock
├── logo.jpeg
├── readme.md
├── App
│   ├── Abstracts
│   │   ├── Data
│   │   │   ├── Finder.php
│   │   │   ├── Sanitizer.php
│   │   │   └── Validator.php
│   │   ├── Database
│   │   │   ├── Migration.php
│   │   │   ├── Model.php
│   │   │   ├── Repository.php
│   │   │   └── Seed.php
│   │   ├── Http
│   │   │   ├── Controller.php
│   │   │   ├── Middleware.php
│   │   │   ├── Request.php
│   │   │   ├── Response.php
│   │   │   └── Service.php
│   │   └── Presentation
│   │       ├── Presenter.php
│   │       └── View.php
│   ├── Contracts
│   │   ├── Data
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
│   │   └── Presentation
│   │       ├── PresenterInterface.php
│   │       └── ViewInterface.php
│   ├── Core
│   │   ├── App.php
│   │   ├── Cache.php
│   │   ├── Config.php
│   │   ├── Database.php
│   │   ├── Logger.php
│   │   ├── Router.php
│   │   └── Session.php
│   ├── Database
│   │   ├── Migrations
│   │   └── Seeds
│   ├── Exceptions
│   │   ├── CacheException.php
│   │   ├── ConfigException.php
│   │   ├── Data
│   │   │   ├── FinderException.php
│   │   │   ├── SanitizationException.php
│   │   │   └── ValidationException.php
│   │   ├── Database
│   │   │   ├── DatabaseException.php
│   │   │   ├── MigrationException.php
│   │   │   ├── ModelException.php
│   │   │   └── RepositoryException.php
│   │   ├── Http
│   │   │   ├── ControllerException.php
│   │   │   ├── MiddlewareException.php
│   │   │   ├── RequestException.php
│   │   │   ├── ResponseException.php
│   │   │   └── ServiceException.php
│   │   ├── Presentation
│   │   │   ├── PresenterException.php
│   │   │   └── ViewException.php
│   │   ├── RouteNotFoundException.php
│   │   └── RouterException.php
│   ├── Helpers
│   │   ├── ArrayHelper.php
│   │   ├── ErrorHelper.php
│   │   ├── ExistenceChecker.php
│   │   ├── ItemRetriever.php
│   │   ├── LoopHelper.php
│   │   └── TypeChecker.php
│   ├── Modules
│   │   ├── AdminModule
│   │   │   ├── Controllers
│   │   │   ├── Middlewares
│   │   │   ├── Models
│   │   │   ├── Presenters
│   │   │   ├── Repositories
│   │   │   ├── Requests
│   │   │   ├── Responses
│   │   │   ├── Routes
│   │   │   └── Views
│   │   ├── CartModule
│   │   │   ├── Controllers
│   │   │   ├── Middlewares
│   │   │   ├── Models
│   │   │   ├── Presenters
│   │   │   ├── Repositories
│   │   │   ├── Requests
│   │   │   ├── Responses
│   │   │   ├── Routes
│   │   │   └── Views
│   │   ├── OrderModule
│   │   │   ├── Controllers
│   │   │   ├── Middlewares
│   │   │   ├── Models
│   │   │   ├── Presenters
│   │   │   ├── Repositories
│   │   │   ├── Requests
│   │   │   ├── Responses
│   │   │   ├── Routes
│   │   │   └── Views
│   │   ├── ShopModule
│   │   │   ├── Controllers
│   │   │   ├── Middlewares
│   │   │   ├── Models
│   │   │   ├── Presenters
│   │   │   ├── Repositories
│   │   │   ├── Requests
│   │   │   ├── Responses
│   │   │   ├── Routes
│   │   │   └── Views
│   │   └── UserModule
│   │       ├── Controllers
│   │       ├── Middlewares
│   │       ├── Models
│   │       ├── Presenters
│   │       ├── Repositories
│   │       ├── Requests
│   │       ├── Responses
│   │       ├── Routes
│   │       └── Views
│   ├── Resources
│   │   ├── css
│   │   ├── images
│   │   └── js
│   ├── Services
│   │   └── CoreService.php
│   ├── Templates
│   │   ├── Components
│   │   ├── Layouts
│   │   ├── Pages
│   │   └── Partials
│   └── Utilities
│       ├── Finders
│       │   ├── DirectoryFinder.php
│       │   └── FileFinder.php
│       ├── Handlers
│       │   ├── CryptoHandler.php
│       │   ├── DataHandler.php
│       │   ├── DataStructureHandler.php
│       │   ├── DateTimeHandler.php
│       │   └── SystemHandler.php
│       ├── Managers
│       │   ├── CompressionManager.php
│       │   ├── DatabaseManager.php
│       │   ├── FileManager.php
│       │   ├── IteratorManager.php
│       │   ├── ReflectionManager.php
│       │   ├── SessionManager.php
│       │   └── SettingsManager.php
│       ├── Rules
│       ├── Sanitation
│       │   ├── CodeSanitizer.php
│       │   ├── FinancedSanitizer.php
│       │   ├── GeneralSanitizer.php
│       │   ├── MediaSanitizer.php
│       │   ├── NetworkSanitizer.php
│       │   ├── NumericSanitizer.php
│       │   └── TextSanitizer.php
│       ├── Traits
│       │   ├── CheckerTrait.php
│       │   ├── ConversionTrait.php
│       │   ├── EncodingTrait.php
│       │   ├── filters
│       │   │   ├── FilterFlagTrait.php
│       │   │   ├── FiltrationTrait.php
│       │   │   ├── SanitationFilterTrait.php
│       │   │   └── ValidationFilterTrait.php
│       │   ├── finder
│       │   │   ├── DirectoryFilterTrait.php
│       │   │   └── FileFilterTrait.php
│       │   ├── LocaleUtilityTrait.php
│       │   ├── ManipulationTrait.php
│       │   ├── MetricsTrait.php
│       │   ├── Patterns
│       │   │   ├── PatternTrait.php
│       │   │   ├── Sanitation
│       │   │   │   ├── CodeSanitationPatternsTrait.php
│       │   │   │   ├── FinanceSanitationPatternsTrait.php
│       │   │   │   ├── MediaSanitationPatternsTrait.php
│       │   │   │   ├── NetworkSanitationPatternsTrait.php
│       │   │   │   ├── NumericSanitationPatternsTrait.php
│       │   │   │   ├── TextSanitationPatternsTrait.php
│       │   │   │   └── UserSanitationPatternsTrait.php
│       │   │   ├── Validation
│       │   │   │   ├── CodeValidationPatternsTrait.php
│       │   │   │   ├── FinanceValidationPatternsTrait.php
│       │   │   │   ├── MediaValidationPatternsTrait.php
│       │   │   │   ├── NetworkValidationPatternsTrait.php
│       │   │   │   ├── NumericValidationPatternsTrait.php
│       │   │   │   ├── TextValidationPatternsTrait.php
│       │   │   │   └── UserValidationPatternsTrait.php
│       └── Validation
│           ├── CodeValidator.php
│           ├── FinanceValidator.php
│           ├── FullValidator.php
│           ├── GeneralValidator.php
│           ├── MediaValidator.php
│           ├── NetworkValidator.php
│           ├── NumericValidator.php
│           └── TextValidator.php
├── Config
│   ├── app.php
│   ├── cache.php
│   ├── cookie.php
│   ├── db.php
│   ├── encryption.php
│   ├── feature.php
│   ├── mail.php
│   └── session.php
├── Data
│   ├── Carts.sql
│   ├── Orders.sql
│   ├── Products.sql
│   └── Users.sql
├── Public
│   ├── assets
│   │   ├── css
│   │   ├── images
│   │   └── js
│   ├── .htaccess
│   └── index.php
├── Storage
│   ├── Cache
│   ├── Logs
│   ├── Secure
│   └── Uploads
├── Tests
│   ├── Integration
│   └── Unit
└── vendor
