-- Database setup for News Website

-- Create database
CREATE DATABASE IF NOT EXISTS news_website;
USE news_website;

-- Create journalists table
CREATE TABLE IF NOT EXISTS journalists (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    avatar VARCHAR(255) DEFAULT NULL,
    bio TEXT DEFAULT NULL,
    title VARCHAR(100) DEFAULT NULL,
    social_facebook VARCHAR(255) DEFAULT NULL,
    social_twitter VARCHAR(255) DEFAULT NULL,
    social_instagram VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create categories table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create articles table
CREATE TABLE IF NOT EXISTS articles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    excerpt TEXT NOT NULL,
    content LONGTEXT NOT NULL,
    image_path VARCHAR(255) DEFAULT NULL,
    image_caption VARCHAR(255) DEFAULT NULL,
    video_url VARCHAR(255) DEFAULT NULL,
    journalist_id INT NOT NULL,
    category_id INT NOT NULL,
    status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
    is_featured BOOLEAN DEFAULT 0,
    is_breaking BOOLEAN DEFAULT 0,
    views INT DEFAULT 0,
    tags VARCHAR(255) DEFAULT NULL,
    published_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (journalist_id) REFERENCES journalists(id),
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- Create comments table
CREATE TABLE IF NOT EXISTS comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    article_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    content TEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE
);

-- Create settings table
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    value TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default categories
INSERT INTO categories (name) VALUES 
('Politique'),
('Économie'),
('Faits Divers'),
('Sports'),
('Santé'),
('Culture'),
('Internationale');

-- Insert default settings
INSERT INTO settings (name, value) VALUES
('site_title', 'Actualités Gabonaises'),
('site_description', 'Votre source d''informations fiable au Gabon'),
('contact_email', 'contact@actualitesgn.com'),
('contact_phone', '+241 12 345 678'),
('contact_address', '123 Boulevard des Médias, Libreville, Gabon'),
('facebook_url', 'https://facebook.com/actualitesgn'),
('twitter_url', 'https://twitter.com/actualitesgn'),
('instagram_url', 'https://instagram.com/actualitesgn'),
('youtube_url', 'https://youtube.com/actualitesgn');

-- Insert sample journalists
INSERT INTO journalists (name, email, avatar, bio, title) VALUES
('Jean Dupont', 'jean.dupont@actualitesgn.com', 'assets/images/journalists/jean.jpg', 'Jean Dupont est un journaliste spécialisé dans l''actualité politique et économique avec plus de 10 ans d''expérience.', 'Rédacteur en chef'),
('Marie Koumba', 'marie.koumba@actualitesgn.com', 'assets/images/journalists/marie.jpg', 'Marie Koumba couvre les événements culturels et sociaux au Gabon et dans la sous-région.', 'Journaliste culturelle'),
('Pierre Nzé', 'pierre.nze@actualitesgn.com', 'assets/images/journalists/pierre.jpg', 'Pierre Nzé est notre expert en sport, suivant particulièrement le football et les compétitions internationales.', 'Journaliste sportif'),
('Sophie Mboulou', 'sophie.mboulou@actualitesgn.com', 'assets/images/journalists/sophie.jpg', 'Sophie Mboulou est spécialisée dans les questions de santé et d''environnement.', 'Journaliste scientifique');

-- Insert sample articles
INSERT INTO articles (title, excerpt, content, image_path, image_caption, journalist_id, category_id, status, is_featured, is_breaking, tags, published_date) VALUES
('Réformes économiques : le gouvernement annonce de nouvelles mesures', 'Le ministre de l''Économie a présenté hier un ensemble de réformes visant à dynamiser l''économie nationale.', '<p>Le ministre de l''Économie a présenté hier un ensemble de réformes visant à dynamiser l''économie nationale et à attirer de nouveaux investisseurs dans les secteurs prioritaires.</p><p>Ces mesures, qui entreront en vigueur dès le mois prochain, comprennent notamment des allègements fiscaux pour les PME, un programme de soutien à l''innovation, et des initiatives pour encourager l''entrepreneuriat des jeunes.</p><p>"Notre objectif est de créer un environnement favorable à la croissance économique et à la création d''emplois", a déclaré le ministre lors d''une conférence de presse.</p><p>Les acteurs économiques saluent globalement ces annonces, mais certains experts pointent des interrogations sur le financement de ces réformes.</p>', 'https://images.pexels.com/photos/3183150/pexels-photo-3183150.jpeg', 'Le ministre de l''Économie lors de l''annonce des nouvelles mesures', 1, 2, 'published', 1, 1, 'économie,réformes,gouvernement', '2023-05-10 14:30:00'),
('Victoire historique des Panthères en qualification pour la Coupe d''Afrique', 'L''équipe nationale de football a remporté hier une victoire décisive qui la rapproche de la qualification.', '<p>Les Panthères du Gabon ont réalisé une performance exceptionnelle hier soir au stade d''Angondjé, en s''imposant 3-0 face à une équipe redoutable.</p><p>Dès les premières minutes, nos joueurs ont montré leur détermination avec un jeu offensif qui a surpris leurs adversaires. Le premier but, marqué à la 18e minute par le capitaine Pierre-Emerick Aubameyang, a donné le ton de la rencontre.</p><p>Le sélectionneur national s''est dit "extrêmement satisfait de l''engagement et de la performance collective" de son équipe. Cette victoire place le Gabon en position favorable pour la qualification à la prochaine Coupe d''Afrique des Nations.</p><p>Les supporters, venus nombreux, ont célébré cette victoire jusqu''au tard dans la nuit dans les rues de Libreville.</p>', 'https://images.pexels.com/photos/46798/the-ball-stadion-football-the-pitch-46798.jpeg', 'Les joueurs célébrant leur victoire au stade d''Angondjé', 3, 4, 'published', 1, 0, 'football,panthères,CAN,qualification', '2023-05-15 22:15:00'),
('Festival Gaboma : la culture gabonaise à l''honneur', 'La 5ème édition du Festival Gaboma s''est ouverte hier à Libreville, célébrant la richesse culturelle du pays.', '<p>La 5ème édition du Festival Gaboma a débuté hier à l''esplanade du bord de mer à Libreville, réunissant artistes, artisans et amateurs de culture dans une célébration vibrante des traditions gabonaises.</p><p>Pendant une semaine, plus de 200 artistes locaux et internationaux proposeront des spectacles, des expositions et des ateliers qui mettent en valeur la diversité culturelle du Gabon.</p><p>"Ce festival est une vitrine exceptionnelle pour nos artistes et un moyen de préserver notre patrimoine culturel", a déclaré le ministre de la Culture lors de la cérémonie d''ouverture.</p><p>Les organisateurs attendent plus de 50 000 visiteurs pour cette édition qui promet d''être la plus importante depuis la création du festival.</p>', 'https://images.pexels.com/photos/2034851/pexels-photo-2034851.jpeg', 'Danse traditionnelle lors de la cérémonie d''ouverture du Festival Gaboma', 2, 6, 'published', 1, 0, 'culture,festival,tradition,art', '2023-05-20 19:45:00'),
('Campagne de vaccination : les objectifs dépassés dans trois provinces', 'Le ministère de la Santé annonce des résultats encourageants pour sa campagne nationale de vaccination.', '<p>La campagne nationale de vaccination lancée il y a trois mois montre des résultats très positifs, avec des taux de couverture qui dépassent les objectifs initiaux dans trois provinces du pays.</p><p>Selon le ministère de la Santé, l''Estuaire, le Haut-Ogooué et l''Ogooué-Maritime ont atteint des taux de vaccination supérieurs à 85%, grâce notamment à l''implication des communautés locales et au travail des équipes médicales mobiles.</p><p>"Ces résultats démontrent l''efficacité de notre stratégie de proximité", a souligné le Dr. Mboulou, directeur de la prévention au ministère de la Santé.</p><p>La campagne se poursuivra dans les autres provinces avec un renforcement des moyens logistiques pour atteindre les zones les plus reculées.</p>', 'https://images.pexels.com/photos/5863365/pexels-photo-5863365.jpeg', 'Une infirmière administrant un vaccin lors de la campagne', 4, 5, 'published', 0, 1, 'santé,vaccination,prévention', '2023-05-25 10:20:00'),
('Tensions diplomatiques : le Gabon appelle au dialogue', 'Face aux tensions croissantes dans la sous-région, le gouvernement gabonais propose sa médiation.', '<p>Le ministre des Affaires étrangères a appelé hier au dialogue entre les pays voisins dont les relations se sont détériorées ces dernières semaines suite à un différend frontalier.</p><p>"Le Gabon, fidèle à sa tradition de pays de paix, propose sa médiation pour résoudre ce conflit par la voie diplomatique", a déclaré le ministre lors d''une conférence de presse.</p><p>Cette initiative gabonaise intervient alors que la communauté internationale s''inquiète de l''escalade des tensions dans la sous-région, avec des mouvements de troupes signalés de part et d''autre de la frontière contestée.</p><p>Les analystes saluent cette démarche diplomatique qui pourrait contribuer à désamorcer la crise avant qu''elle ne prenne une tournure plus grave.</p>', 'https://images.pexels.com/photos/1056553/pexels-photo-1056553.jpeg', 'Le ministre des Affaires étrangères lors de sa déclaration', 1, 7, 'published', 1, 1, 'diplomatie,conflit,médiation,politique internationale', '2023-06-01 16:10:00'),
('Inauguration du nouveau campus universitaire', 'Le président de la République a inauguré hier le nouveau campus de l''Université Omar Bongo.', '<p>C''est un projet attendu depuis longtemps qui se concrétise enfin. Le président de la République a procédé hier à l''inauguration du nouveau campus de l''Université Omar Bongo (UOB), un complexe moderne qui va transformer l''enseignement supérieur au Gabon.</p><p>Ce campus, qui s''étend sur plus de 50 hectares, comprend des amphithéâtres équipés des dernières technologies, des laboratoires de recherche, une bibliothèque numérique, des logements étudiants et des installations sportives.</p><p>"Cet investissement majeur dans l''éducation de notre jeunesse témoigne de notre engagement pour l''avenir du pays", a déclaré le chef de l''État lors de la cérémonie d''inauguration.</p><p>Les premiers étudiants intégreront le nouveau campus dès la rentrée universitaire prochaine, avec une capacité d''accueil de 15 000 étudiants.</p>', 'https://images.pexels.com/photos/267885/pexels-photo-267885.jpeg', 'Vue aérienne du nouveau campus universitaire', 2, 1, 'published', 0, 0, 'éducation,université,infrastructure', '2023-06-05 11:30:00'),
('Le secteur minier en pleine expansion : création de 500 emplois', 'Une nouvelle exploitation minière va ouvrir dans la province du Haut-Ogooué, créant des centaines d''emplois.', '<p>Le secteur minier gabonais poursuit sa croissance avec l''annonce de l''ouverture prochaine d''une nouvelle exploitation dans la province du Haut-Ogooué.</p><p>Ce projet, fruit d''un partenariat entre l''État gabonais et un consortium international, devrait générer environ 500 emplois directs et plus de 1000 emplois indirects dans la région.</p><p>"Cette nouvelle exploitation s''inscrit dans notre stratégie de diversification économique et de valorisation des ressources naturelles du pays", a expliqué le ministre des Mines lors d''une visite sur le site.</p><p>Les travaux de construction des infrastructures débuteront le mois prochain, pour une mise en exploitation prévue d''ici 18 mois.</p>', 'https://images.pexels.com/photos/2101140/pexels-photo-2101140.jpeg', 'Site de la future exploitation minière dans le Haut-Ogooué', 1, 2, 'published', 0, 0, 'économie,mines,emploi,développement', '2023-06-10 09:45:00'),
('Hausse inquiétante des accidents de la route', 'Les autorités alertent sur l''augmentation des accidents de la circulation et annoncent des mesures.', '<p>Les statistiques des trois derniers mois révèlent une augmentation préoccupante du nombre d''accidents de la route, particulièrement sur l''axe Libreville-Lambaréné.</p><p>Selon le rapport de la gendarmerie nationale, le nombre d''accidents a augmenté de 35% par rapport à la même période l''année dernière, avec une forte proportion d''accidents impliquant des excès de vitesse et la consommation d''alcool.</p><p>Face à cette situation, les autorités annoncent un renforcement des contrôles routiers et le lancement d''une campagne de sensibilisation dans les médias et les établissements scolaires.</p><p>"La sécurité routière est l''affaire de tous", a rappelé le directeur général des transports, appelant les usagers de la route à plus de prudence et de responsabilité.</p>', 'https://images.pexels.com/photos/2119713/pexels-photo-2119713.jpeg', 'Agents de la circulation lors d''un contrôle routier', 4, 3, 'published', 0, 1, 'sécurité,transport,accidents,prévention', '2023-06-15 14:20:00');