## Project layout
```
.
└── BroadworksTardis
    ├── Storage                      - Storage controllers for various storage backends
    │   ├── XSI.php 
    │   ├── OCI.php
    │   ├── Mongo.php
    │   ├── Git.php
    │   └── Filesystem.php
    ├── Semantics                    - userId needed in requests becomes UserId in responses
    │   ├── TranslationInterface.php  \ These classes translate names to the required format
    │   ├── SimpleTypes.php
    │   └── ComplexTypes.php
    ├── Scheduals                    - Schedualer for automated migrates/backups etc..
    ├── Processes
    │   ├── Worker.php               - Works the jobs from the queue
    │   └── Manager.php              - Keeps workers in sync, auto-scales with demand
    ├── Migrator
    │   └── Migrator.php             - Orchestrates an migration
    ├── Interfaces
    │   ├── Console.php              - Interactive console for admins
    │   └── API.php                  - API for external integration
    └── Controllers                  - Control each element in question
        ├── Users.php
        ├── Services.php
        ├── Numbers.php
        ├── Groups.php               
        ├── Enterprises.php
        ├── Domains.php
        ├── Devices.php
        ├── Commands.php
        └── Admins.php

8 directories, 25 files
```
