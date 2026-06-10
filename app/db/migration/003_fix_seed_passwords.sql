-- Mot de passe de démonstration: MotDePasse123!
UPDATE users
SET password_hash = '$2y$10$.ft.Z9VfhvrkovA3r0xIOeFgsfTvUw3kA70pz3onZHs2M2VqTAzVG',
    updated_at = CURRENT_TIMESTAMP
WHERE afric_number IN ('12345678', 'AGENT001');
