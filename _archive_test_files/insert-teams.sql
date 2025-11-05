-- Insert default teams
INSERT IGNORE INTO wp_bkgt_teams (name, slug, team_type, description) VALUES
('Herrlag', 'herrlag', 'men', 'Herrarnas representationslag'),
('Damlag', 'damlag', 'women', 'Damernas representationslag'),
('U19', 'u19', 'youth', 'U19 ungdomslag'),
('U17', 'u17', 'youth', 'U17 ungdomslag'),
('U15', 'u15', 'youth', 'U15 ungdomslag'),
('U13', 'u13', 'youth', 'U13 ungdomslag');