# P8-project
**Openclassrooms - Improve an existing ToDo & Co application**

Demo application base URL: https://rivierarts.fr  
Administrator access:  
- Login: admin  
- Password: @D31n7wd  

Test report: http://test-coverage.rivierarts.fr/  
Access:  
- Login: admin  
- Password: 7E5t@dW1n  

Codacy and Codeclimate code quality analysis are accessible here:  
- https://app.codacy.com/manual/ericc06/P8-project/dashboard  
- https://codeclimate.com/github/ericc06/P8-project  

## To migrate an existing application for a contributing developer:

The general process will consist in:
- Keeping all data stored in the database.  
- Removing the whole content of the Symfony application (code, configuration...).  
- Initializing the local Git repository and link it with the GitHub project repository.  
- Making a "git pull" of the master branch.  
- composer install  
- Configuring the ".env" file to connect the database.  
- Modifying the application web site root directory.  
- Migrating the database.  
- Configuring HTTPS.  
- Setting the test environment.
- Running the tests and making the report securely accessible.

1. In the root folder of the existing Symfony application:

        rm -rf *
        rm -rf .*
        git init .
        git remote add origin git@github.com:ericc06/P8-project.git
        git pull origin master
        composer install

2. Modify the ".env" file to connect the database. For example:

        DATABASE_URL=mysql://username:pwd@127.0.0.1:3306/db_name?serverVersion=5.7

3. In the web server configuration (or in the web host interface), modify the root directory of the application web site: replace "web" (SF 3.x) with "public" (SF 4.x).

4. Execute the database migration:

        php bin/console doctrine:migrations:migrate

5. To force HTTPS redirection, add the following lines at the beginning of the .htaccess file located in the "/public" folder of the web site (replace domaine.tld with your domain name and extention):

        # Redirecting to HTTPS
        RewriteCond %{SERVER_PORT} ^80$ [OR]
        RewriteCond %{HTTPS} =off
        RewriteRule ^(.*)$ https://domaine.tld/$1 [R=301,L]

        # Redirecting www to non-www on HTTPS
        RewriteCond %{HTTP_HOST} ^www\.domaine\.tld [NC]
        RewriteRule ^(.*)$ https://domaine.tld/$1 [R=301,L]

You can also configure a real SSL certificate on your domain to avoid browser security warnings.

6. For unit and functional tests, a dedicated database must be created. The ".env.test" file must be modified to connect this test database:

        DATABASE_URL=mysql://username:pwd@127.0.0.1:3306/test_db_name?serverVersion=5.7

7. Creation of this database tables and fixtures loading:

        php bin/console doctrine:schema:create --env=test
        php bin/console doctrine:fixtures:load --env=test

8. We can check that PHPUnit works correctly:

        php bin/phpunit --help

9. Then we launch the tests and we check that PHPUnit is able to generate the test report:

        php bin/phpunit --coverage-html ./test-coverage

Note : If the "Error: No code coverage driver is available" message appears, it means that Xdebug needs to be activated in the "php.ini" file.

10. Test report files are created in a "test-coverage" folder which is not included in the "public" folder on purpose. This way the report can't be accessed from the application web site as it contains sensible information.  
To make it accessible, for example, create a subdomain such as "test-coverage.domain.tld" and make it point to the "test-coverage" folder.  
Finally, secure this access with a password thanks to .htpasswd and .htaccess files:  
The .htpasswd will be located in the root directory of the Symfony application (i.e. at the same level than the "test-coverage" folder) and its content can be generated with this tool: https://hostingcanada.org/htpasswd-generator/  
The .htaccess will be located in the "test-coverage" folder, and its content will look like this:

        #Protect Directory
        AuthName "Test coverage"
        AuthType Basic
        AuthUserFile <system_path_of_the_root_folder_of_the_Symfony_app>/.htpasswd
        Require valid-user

## How to contribute to the project respecting a high level of quality and performance:

1. It should have already be done by the projet repository administrator, but first check that a Codacy account is configured to check the code quality of the "dev" branch of the project.

2. Use a code editor which includes a real-time code quality analyser such as PHPCS.

3. Respect the 5 following technical recommendations to benefit from PHP 7 optimizations (read https://blog.blackfire.io/php-7-performance-improvements-packed-arrays.html):

- Use of compacted arrays (keys are ascending integers).  
- Reuse already declared variables whenever possible.
- Use wrapped strings rather than chain concatenation.
- Let PHP 7 manage any incompatible references (there is nothing to do).
- Use of immutable arrays (containing only values, and no variables).

4. Before each "git push", use PHPCS Fixer on the command line with Symfony + PRS2 rules (or PSR12 if available, because PSR2 is now obsolete). Command examples for Windows:
For a dry run:

        php-cs-fixer.bat fix --dry-run --verbose ./src --rules=@PSR2,@Symfony

For an effective code fix:

        php-cs-fixer.bat fix --verbose ./src --rules=@PSR2,@Symfony

5. Commit and push code into a dedicated branch (feature, fix or other).

6. Propose a Pull Request from the dedicated branch to the "dev" branch. A Codacy analysis should be automatically triggered and appear in the Pull Request interface.

7. If the analysis result is negative (Codacy warns about an increased number of code quality issues), fix the issues and commit/push again.
Else, if the analysis result is positive, let the project repository administrator take care of the Pull Request. He will receive an email about the analysis result.
**Important: Codacy code quality analysis must never return anything else than a A-grade badge.**

8. Frequently check the application performance with Blackfire to avoid any performance regression.