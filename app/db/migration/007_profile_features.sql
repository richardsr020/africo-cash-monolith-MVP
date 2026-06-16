ALTER TABLE user_onboarding ADD COLUMN preferences TEXT DEFAULT '{"notify_sms":true,"notify_email":true,"notify_push":true,"two_factor_enabled":false,"login_alerts":true,"transaction_alerts":true,"marketing":false}';
ALTER TABLE user_onboarding ADD COLUMN profession VARCHAR(100) DEFAULT '';
ALTER TABLE user_onboarding ADD COLUMN address TEXT DEFAULT '';
