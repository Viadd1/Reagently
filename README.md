# Reagently

Reagently is a laboratory information management system (LIMS) designed for keeping track of reagents in laboratory environments. The reservation function of the system allows users to see when reagents will be taken, which aims to aid users in planning projects and reagent orders as well as keeping track of storage.   

The system can be run locally on a server stack supporting MySQL and PHP (the project was developed using MAMP) and can be run using the HTTPS protocol, refer to HTTPS_conversion.md for more details. To run Reagently, do the following:

1. Clone repo locally on your machine.
2. Install dependencies for email sending (PHPMailer and phpdotenv), preferably using the Composer file manager.
3. Construct the database using queries in create_tables.sql.
4. Create your first account which is automatically assigned as the Admin account.
5. Have fun adding more users and inserting and reserving your reagents!

For more information on how Reagently is structured, refer to File_documentation.md and Structure.md.

The system was developed by Sofia Englundh, David Eriksson, Ralitsa Shopova, Sigrid Varg and Yufei Wang.