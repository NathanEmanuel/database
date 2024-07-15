# Database managers (DBMs)

## Creating the databases and tables

Each DBM has a `createTable()` method that automatically creates the tables for the DBMs database. The database itself still has to be created by you. Remember that the DBM's user needs to have permission to create tables if you want to use this method.

## Using the DBM

The DBMs have multiple methods for operations that they can perform on the database. What each method does and how it is used is added as PHPDoc in the source code and this should be sufficient for you to be able to learn how to work with the DBMs.

You can add more operations by either modifying the DBM or by subclassing it. Pull requests are welcome if you have made modification that you believe might also be useful for others.
