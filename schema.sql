-- PostgreSQL Schema for Investment REST API Backend
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(30),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);

CREATE TABLE IF NOT EXISTS wallets (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL UNIQUE REFERENCES users(id) ON DELETE CASCADE,
    balance NUMERIC(15, 2) NOT NULL DEFAULT 0.00 CHECK (balance >= 0.00),
    currency VARCHAR(3) NOT NULL DEFAULT 'USD',
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS transactions (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    type VARCHAR(20) NOT NULL CHECK (type IN ('deposit', 'withdrawal', 'investment', 'payout', 'refund')),
    amount NUMERIC(15, 2) NOT NULL CHECK (amount > 0),
    fee NUMERIC(15, 2) NOT NULL DEFAULT 0.00 CHECK (fee >= 0),
    status VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'approved', 'rejected', 'completed', 'cancelled')),
    payment_method VARCHAR(50),             -- 'GCash', 'Maya', 'Bank Transfer', 'Crypto USDT'
    reference_number VARCHAR(100),          -- Client transaction reference ID
    payout_account_details TEXT,            -- Client destination account details for withdrawal
    proof_image_url TEXT,                   -- Uploaded receipt proof image
    rejection_reason TEXT,                  -- Reason if rejected by compliance
    reference VARCHAR(64) NOT NULL UNIQUE,  -- System internal unique tracking reference
    description TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_transactions_user_id ON transactions(user_id);
CREATE INDEX IF NOT EXISTS idx_transactions_status ON transactions(status);
CREATE INDEX IF NOT EXISTS idx_transactions_type ON transactions(type);
CREATE INDEX IF NOT EXISTS idx_transactions_created_at ON transactions(created_at DESC);

CREATE TABLE IF NOT EXISTS investment_plans (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    min_amount NUMERIC(15, 2) NOT NULL CHECK (min_amount > 0),
    max_amount NUMERIC(15, 2) NOT NULL CHECK (max_amount >= min_amount),
    return_rate_percent NUMERIC(5, 2) NOT NULL CHECK (return_rate_percent > 0),
    duration_days INT NOT NULL CHECK (duration_days > 0),
    payout_interval VARCHAR(20) NOT NULL DEFAULT 'maturity' CHECK (payout_interval IN ('daily', 'weekly', 'monthly', 'maturity')),
    risk_level VARCHAR(20) NOT NULL DEFAULT 'moderate' CHECK (risk_level IN ('low', 'moderate', 'high')),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS investments (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    plan_id INT NOT NULL REFERENCES investment_plans(id) ON DELETE RESTRICT,
    principal_amount NUMERIC(15, 2) NOT NULL CHECK (principal_amount > 0),
    expected_return_amount NUMERIC(15, 2) NOT NULL,
    total_payout_amount NUMERIC(15, 2) NOT NULL,
    start_date TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    end_date TIMESTAMP WITH TIME ZONE NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'completed', 'cancelled')),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_investments_user_id ON investments(user_id);
CREATE INDEX IF NOT EXISTS idx_investments_status ON investments(status);

INSERT INTO investment_plans (name, slug, description, min_amount, max_amount, return_rate_percent, duration_days, payout_interval, risk_level)
VALUES 
    ('Treasury Yield Shield', 'treasury-yield', 'Low-risk sovereign debt and government bonds investment.', 50.00, 5000.00, 5.25, 30, 'maturity', 'low'),
    ('Blue-Chip Growth Fund', 'blue-chip-growth', 'Balanced portfolio of high-dividend global companies and index assets.', 100.00, 25000.00, 12.50, 90, 'monthly', 'moderate'),
    ('Emerging Tech Venture', 'emerging-tech', 'High-growth tech ventures and green energy infrastructure.', 500.00, 100000.00, 24.00, 180, 'maturity', 'high')
ON CONFLICT (slug) DO NOTHING;