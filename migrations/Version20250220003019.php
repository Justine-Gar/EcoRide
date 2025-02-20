<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250220003019 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE carpool_users (id_carpool INT NOT NULL, id_user INT NOT NULL, INDEX IDX_B6C38CFA37C039BC (id_carpool), INDEX IDX_B6C38CFA6B3CA4B (id_user), PRIMARY KEY(id_carpool, id_user)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE cars (id_cars INT AUTO_INCREMENT NOT NULL, id_user INT NOT NULL, marque VARCHAR(50) NOT NULL, modele VARCHAR(50) NOT NULL, color VARCHAR(50) NOT NULL, energie VARCHAR(50) NOT NULL, nbr_places INT NOT NULL, license_plate VARCHAR(50) NOT NULL, first_registration DATE NOT NULL, INDEX IDX_95C71D146B3CA4B (id_user), PRIMARY KEY(id_cars)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE reviews (id_review INT AUTO_INCREMENT NOT NULL, id_user INT NOT NULL, id_sender INT NOT NULL, id_recipient INT NOT NULL, id_carpool INT NOT NULL, comment VARCHAR(250) NOT NULL, note NUMERIC(5, 1) NOT NULL, statut VARCHAR(50) NOT NULL, INDEX IDX_6970EB0F6B3CA4B (id_user), INDEX IDX_6970EB0F7937FF22 (id_sender), INDEX IDX_6970EB0FE831476E (id_recipient), INDEX IDX_6970EB0F37C039BC (id_carpool), PRIMARY KEY(id_review)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_preferences (id_user_preference INT AUTO_INCREMENT NOT NULL, id_user INT NOT NULL, id_preference_type INT NOT NULL, choose_value VARCHAR(50) NOT NULL, INDEX IDX_402A6F606B3CA4B (id_user), INDEX IDX_402A6F601277CC8D (id_preference_type), PRIMARY KEY(id_user_preference)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_roles (id_user INT NOT NULL, id_role INT NOT NULL, INDEX IDX_54FCD59F6B3CA4B (id_user), INDEX IDX_54FCD59FDC499668 (id_role), PRIMARY KEY(id_user, id_role)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE carpool_users ADD CONSTRAINT FK_B6C38CFA37C039BC FOREIGN KEY (id_carpool) REFERENCES carpools (id_carpool)');
        $this->addSql('ALTER TABLE carpool_users ADD CONSTRAINT FK_B6C38CFA6B3CA4B FOREIGN KEY (id_user) REFERENCES users (id_user)');
        $this->addSql('ALTER TABLE cars ADD CONSTRAINT FK_95C71D146B3CA4B FOREIGN KEY (id_user) REFERENCES users (id_user)');
        $this->addSql('ALTER TABLE reviews ADD CONSTRAINT FK_6970EB0F6B3CA4B FOREIGN KEY (id_user) REFERENCES users (id_user)');
        $this->addSql('ALTER TABLE reviews ADD CONSTRAINT FK_6970EB0F7937FF22 FOREIGN KEY (id_sender) REFERENCES users (id_user)');
        $this->addSql('ALTER TABLE reviews ADD CONSTRAINT FK_6970EB0FE831476E FOREIGN KEY (id_recipient) REFERENCES users (id_user)');
        $this->addSql('ALTER TABLE reviews ADD CONSTRAINT FK_6970EB0F37C039BC FOREIGN KEY (id_carpool) REFERENCES carpools (id_carpool)');
        $this->addSql('ALTER TABLE user_preferences ADD CONSTRAINT FK_402A6F606B3CA4B FOREIGN KEY (id_user) REFERENCES users (id_user)');
        $this->addSql('ALTER TABLE user_preferences ADD CONSTRAINT FK_402A6F601277CC8D FOREIGN KEY (id_preference_type) REFERENCES preference_types (id_preference_types)');
        $this->addSql('ALTER TABLE user_roles ADD CONSTRAINT FK_54FCD59F6B3CA4B FOREIGN KEY (id_user) REFERENCES users (id_user)');
        $this->addSql('ALTER TABLE user_roles ADD CONSTRAINT FK_54FCD59FDC499668 FOREIGN KEY (id_role) REFERENCES roles (id_role)');
        $this->addSql('ALTER TABLE preference_types ADD CONSTRAINT FK_6B9F128B6B3CA4B FOREIGN KEY (id_user) REFERENCES users (id_user)');
        $this->addSql('ALTER TABLE users CHANGE email email VARCHAR(255) NOT NULL, CHANGE profil_picture profil_picture LONGTEXT DEFAULT NULL, CHANGE credits credits INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE carpool_users DROP FOREIGN KEY FK_B6C38CFA37C039BC');
        $this->addSql('ALTER TABLE carpool_users DROP FOREIGN KEY FK_B6C38CFA6B3CA4B');
        $this->addSql('ALTER TABLE cars DROP FOREIGN KEY FK_95C71D146B3CA4B');
        $this->addSql('ALTER TABLE reviews DROP FOREIGN KEY FK_6970EB0F6B3CA4B');
        $this->addSql('ALTER TABLE reviews DROP FOREIGN KEY FK_6970EB0F7937FF22');
        $this->addSql('ALTER TABLE reviews DROP FOREIGN KEY FK_6970EB0FE831476E');
        $this->addSql('ALTER TABLE reviews DROP FOREIGN KEY FK_6970EB0F37C039BC');
        $this->addSql('ALTER TABLE user_preferences DROP FOREIGN KEY FK_402A6F606B3CA4B');
        $this->addSql('ALTER TABLE user_preferences DROP FOREIGN KEY FK_402A6F601277CC8D');
        $this->addSql('ALTER TABLE user_roles DROP FOREIGN KEY FK_54FCD59F6B3CA4B');
        $this->addSql('ALTER TABLE user_roles DROP FOREIGN KEY FK_54FCD59FDC499668');
        $this->addSql('DROP TABLE carpool_users');
        $this->addSql('DROP TABLE cars');
        $this->addSql('DROP TABLE reviews');
        $this->addSql('DROP TABLE user_preferences');
        $this->addSql('DROP TABLE user_roles');
        $this->addSql('ALTER TABLE preference_types DROP FOREIGN KEY FK_6B9F128B6B3CA4B');
        $this->addSql('ALTER TABLE users CHANGE email email VARCHAR(50) NOT NULL, CHANGE profil_picture profil_picture TEXT DEFAULT NULL, CHANGE credits credits INT DEFAULT 0 NOT NULL');
    }
}
