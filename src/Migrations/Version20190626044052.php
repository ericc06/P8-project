<?php

// namespace is arbitrary but should be different from App\Migrations
// as migrations classes should NOT be autoloaded
namespace DoctrineMigrations;

use App\Entity\User;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20190626044052 extends AbstractMigration implements ContainerAwareInterface
{
    private $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf(
            'mysql' !== $this->connection->getDatabasePlatform()->getName(),
            'Migration can only be executed safely on \'mysql\'.'
        );

        // Adding the "roles" column to the "user" table.
        $this->addSql('ALTER TABLE user ADD roles LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\'');
        // Giving the "ROLE_USER" role to all existing users.
        $this->addSql('UPDATE user SET roles = \''.serialize(['ROLE_USER']).'\' WHERE roles = ""');
        // Creating the "admin" user with "ROLE_ADMIN" role.
        $admin = new User();
        $passwordEncoder = $this->container->get('security.password_encoder');
        $password = $passwordEncoder->encodePassword($admin, "admin");
        //$password = Tools::encryptPwd('admin');
        $query = 'INSERT INTO user (username, password, email, roles) ';
        $query .= 'VALUES ("admin", "'.$password.'", "admin@system.user", \'';
        $query .= serialize(['ROLE_ADMIN', 'ROLE_USER']).'\')';
        $this->addSql($query);
        // Creating the anonymous user.
        $query = 'INSERT INTO user (username, password, email, roles) ';
        $query .= 'VALUES ("anonymous@system.user", "ano-pwd", "anonymous@system.user", \'';
        $query .= serialize(['ROLE_USER']).'\')';
        $this->addSql($query);
        // Adding the "user_id" column to the "tack" table.
        $this->addSql('ALTER TABLE task ADD user_id INT NOT NULL');
        // Linking all existing tasks with the anonymous user.
        $this->addSql('UPDATE task SET user_id = LAST_INSERT_ID() WHERE user_id = 0');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB25A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_527EDB25A76ED395 ON task (user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf(
            'mysql' !== $this->connection->getDatabasePlatform()->getName(),
            'Migration can only be executed safely on \'mysql\'.'
        );

        $this->addSql('DROP INDEX IDX_527EDB25A76ED395 ON task');
        $this->addSql('ALTER TABLE task DROP FOREIGN KEY FK_527EDB25A76ED395');
        $this->addSql('ALTER TABLE task DROP user_id');
        $this->addSql('DELETE FROM user WHERE email = "anonymous@system.user"');
        $this->addSql('DELETE FROM user WHERE email = "admin@system.user"');
        $this->addSql('ALTER TABLE user DROP roles');
    }
}
