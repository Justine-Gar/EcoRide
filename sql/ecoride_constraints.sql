USE ecoride;

-- Création des clés étrangères --
ALTER TABLE `user_roles`
  ADD CONSTRAINT fk_user_roles_users FOREIGN KEY (id_user) REFERENCES users (id_user) ON DELETE CASCADE,
  ADD CONSTRAINT fk_user_roles_role FOREIGN KEY (id_role) REFERENCES roles (id_role);

ALTER TABLE `cars`
  ADD CONSTRAINT fk_cars_users FOREIGN KEY (id_user) REFERENCES users (id_user) ON DELETE CASCADE;

ALTER TABLE `carpools`
  ADD CONSTRAINT fk_carpools_users FOREIGN KEY (id_user) REFERENCES users (id_user) ON DELETE CASCADE;

ALTER TABLE `carpool_users`
    ADD CONSTRAINT fk_carpool_users_carpool FOREIGN KEY (id_carpool) REFERENCES carpools (id_carpool) ON DELETE CASCADE,
    ADD CONSTRAINT fk_carpool_users_users FOREIGN KEY (id_user) REFERENCES users (id_user) ON DELETE CASCADE;

ALTER TABLE reviews
    ADD CONSTRAINT fk_reviews_sender FOREIGN KEY (id_sender) REFERENCES users (id_user) ON DELETE CASCADE,
    ADD CONSTRAINT fk_reviews_recipient FOREIGN KEY (id_recipient) REFERENCES users (id_user) ON DELETE CASCADE,
    ADD CONSTRAINT fk_reviews_carpool FOREIGN KEY (id_carpool) REFERENCES carpools (id_carpool) ON DELETE CASCADE,
    ADD CONSTRAINT fk_reviews_users FOREIGN KEY (id_user) REFERENCES users (id_user) ON DELETE CASCADE;

ALTER TABLE `preferences_types`
    ADD CONSTRAINT fk_preferences_types_users FOREIGN KEY (id_user) REFERENCES users (id_user) ON DELETE CASCADE;

ALTER TABLE `user_preferences`
    ADD CONSTRAINT fk_user_preferences_users FOREIGN KEY (id_user) REFERENCES users (id_user) ON DELETE CASCADE,
    ADD CONSTRAINT fk_user_preferences_preference_types FOREIGN KEY (id_preference_types) REFERENCES preferences_types (id_preference_types) ON DELETE CASCADE;
