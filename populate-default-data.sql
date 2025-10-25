-- Insert default manufacturers
INSERT INTO wp_bkgt_manufacturers (name, manufacturer_id) VALUES
('Bosch', 'BOS'),
('Makita', 'MAK'),
('DeWalt', 'DEW'),
('Hilti', 'HIL'),
('Milwaukee', 'MIL');

-- Insert default item types
INSERT INTO wp_bkgt_item_types (name, item_type_id, custom_fields) VALUES
('Slagborrmaskin', 'HAM', '{"power": "Watt", "voltage": "Volt"}'),
('Skruvdragare', 'SCD', '{"torque": "Nm", "battery": "Ah"}'),
('Cirkelsåg', 'CSW', '{"blade_size": "mm", "power": "Watt"}'),
('Multiverktyg', 'OSC', '{"accessories": "st"}'),
('Batteriladdare', 'CHR', '{"output": "A", "input": "V"}');