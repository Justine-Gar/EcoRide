<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250217160150 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE car (id_cars INT AUTO_INCREMENT NOT NULL, id_user INT NOT NULL, marque VARCHAR(50) NOT NULL, modele VARCHAR(50) NOT NULL, color VARCHAR(50) NOT NULL, energie VARCHAR(50) NOT NULL, nbr_places INT NOT NULL, license_plate VARCHAR(50) NOT NULL, first_registration DATE NOT NULL, INDEX IDX_773DE69D6B3CA4B (id_user), PRIMARY KEY(id_cars)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE carpool (id_carpool INT AUTO_INCREMENT NOT NULL, id_user INT NOT NULL, date_start DATE NOT NULL, location_start VARCHAR(50) NOT NULL, hour_start TIME NOT NULL, date_reach DATE NOT NULL, location_reach VARCHAR(50) NOT NULL, hour_reach TIME NOT NULL, statut VARCHAR(50) NOT NULL, credits INT NOT NULL, nbr_places INT NOT NULL, lat_start NUMERIC(15, 2) DEFAULT NULL, lng_start NUMERIC(15, 2) DEFAULT NULL, lat_reach NUMERIC(15, 2) DEFAULT NULL, lng_reach NUMERIC(15, 2) DEFAULT NULL, INDEX IDX_E95D90CC6B3CA4B (id_user), PRIMARY KEY(id_carpool)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE carpool_users (id_carpool INT NOT NULL, id_user INT NOT NULL, INDEX IDX_B6C38CFA37C039BC (id_carpool), INDEX IDX_B6C38CFA6B3CA4B (id_user), PRIMARY KEY(id_carpool, id_user)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE preference_type (id_preference_types INT AUTO_INCREMENT NOT NULL, id_user INT DEFAULT NULL, name VARCHAR(50) NOT NULL, is_systeme TINYINT(1) NOT NULL, INDEX IDX_FB290F056B3CA4B (id_user), PRIMARY KEY(id_preference_types)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE review (id_review INT AUTO_INCREMENT NOT NULL, id_user INT NOT NULL, comment VARCHAR(250) NOT NULL, note NUMERIC(5, 1) NOT NULL, statut VARCHAR(50) NOT NULL, INDEX IDX_794381C66B3CA4B (id_user), PRIMARY KEY(id_review)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE role (id_role INT AUTO_INCREMENT NOT NULL, name_role VARCHAR(50) NOT NULL, PRIMARY KEY(id_role)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id_user INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, firstname VARCHAR(50) NOT NULL, email VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, phone_number VARCHAR(50) DEFAULT NULL, profil_picture LONGTEXT DEFAULT NULL, PRIMARY KEY(id_user)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_roles (id_user INT NOT NULL, id_role INT NOT NULL, INDEX IDX_54FCD59F6B3CA4B (id_user), INDEX IDX_54FCD59FDC499668 (id_role), PRIMARY KEY(id_user, id_role)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_preference (id_user_preference INT AUTO_INCREMENT NOT NULL, id_user INT NOT NULL, id_preference_type INT NOT NULL, choose_value VARCHAR(50) NOT NULL, INDEX IDX_FA0E76BF6B3CA4B (id_user), INDEX IDX_FA0E76BF1277CC8D (id_preference_type), PRIMARY KEY(id_user_preference)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE car ADD CONSTRAINT FK_773DE69D6B3CA4B FOREIGN KEY (id_user) REFERENCES user (id_user)');
        $this->addSql('ALTER TABLE carpool ADD CONSTRAINT FK_E95D90CC6B3CA4B FOREIGN KEY (id_user) REFERENCES user (id_user)');
        $this->addSql('ALTER TABLE carpool_users ADD CONSTRAINT FK_B6C38CFA37C039BC FOREIGN KEY (id_carpool) REFERENCES carpool (id_carpool)');
        $this->addSql('ALTER TABLE carpool_users ADD CONSTRAINT FK_B6C38CFA6B3CA4B FOREIGN KEY (id_user) REFERENCES user (id_user)');
        $this->addSql('ALTER TABLE preference_type ADD CONSTRAINT FK_FB290F056B3CA4B FOREIGN KEY (id_user) REFERENCES user (id_user)');
        $this->addSql('ALTER TABLE review ADD CONSTRAINT FK_794381C66B3CA4B FOREIGN KEY (id_user) REFERENCES user (id_user)');
        $this->addSql('ALTER TABLE user_roles ADD CONSTRAINT FK_54FCD59F6B3CA4B FOREIGN KEY (id_user) REFERENCES user (id_user)');
        $this->addSql('ALTER TABLE user_roles ADD CONSTRAINT FK_54FCD59FDC499668 FOREIGN KEY (id_role) REFERENCES role (id_role)');
        $this->addSql('ALTER TABLE user_preference ADD CONSTRAINT FK_FA0E76BF6B3CA4B FOREIGN KEY (id_user) REFERENCES user (id_user)');
        $this->addSql('ALTER TABLE user_preference ADD CONSTRAINT FK_FA0E76BF1277CC8D FOREIGN KEY (id_preference_type) REFERENCES preference_type (id_preference_types)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE car DROP FOREIGN KEY FK_773DE69D6B3CA4B');
        $this->addSql('ALTER TABLE carpool DROP FOREIGN KEY FK_E95D90CC6B3CA4B');
        $this->addSql('ALTER TABLE carpool_users DROP FOREIGN KEY FK_B6C38CFA37C039BC');
        $this->addSql('ALTER TABLE carpool_users DROP FOREIGN KEY FK_B6C38CFA6B3CA4B');
        $this->addSql('ALTER TABLE preference_type DROP FOREIGN KEY FK_FB290F056B3CA4B');
        $this->addSql('ALTER TABLE review DROP FOREIGN KEY FK_794381C66B3CA4B');
        $this->addSql('ALTER TABLE user_roles DROP FOREIGN KEY FK_54FCD59F6B3CA4B');
        $this->addSql('ALTER TABLE user_roles DROP FOREIGN KEY FK_54FCD59FDC499668');
        $this->addSql('ALTER TABLE user_preference DROP FOREIGN KEY FK_FA0E76BF6B3CA4B');
        $this->addSql('ALTER TABLE user_preference DROP FOREIGN KEY FK_FA0E76BF1277CC8D');
        $this->addSql('DROP TABLE car');
        $this->addSql('DROP TABLE carpool');
        $this->addSql('DROP TABLE carpool_users');
        $this->addSql('DROP TABLE preference_type');
        $this->addSql('DROP TABLE review');
        $this->addSql('DROP TABLE role');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE user_roles');
        $this->addSql('DROP TABLE user_preference');
    }
}
