Broadworks Tardis
===================

PHP Application for bulk user management
===


- Migrate users/groups/enterprises between AS clusers
- REST api for integration with other services
- CLI for provisioning staff user management
- Export & import provisioning data
- Audit history via configuration snapshots
- Bulk changes of subscriber data
- Multiple storage backends - file, git, NoSQL


_Closed source, private project.

```
.
└── BroadworksTardis
    ├── Controllers
    │   ├── Admins.php
    │   ├── Commands.php
    │   ├── Devices.php
    │   ├── Domains.php
    │   ├── Enterprise.php
    │   ├── Groups.php
    │   ├── Numbers.php
    │   ├── Services.php
    │   └── Users.php
    ├── Interfaces
    │   ├── API.php
    │   └── Console.php
    ├── Migrator
    │   └── Migrator.php
    ├── Processes
    │   ├── Manager.php
    │   └── Worker.php
    ├── Scheduals
    ├── Semantics
    │   ├── ComplexTypes.php
    │   ├── SimpleTypes.php
    │   └── TranslationInterface.php
    └── Storage
        ├── Filesystem.php
        ├── Git.php
        ├── Mongo.php
        ├── OCI.php
        └── XSI.php

8 directories, 22 files
```
