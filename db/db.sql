-- Table: Profile
CREATE TABLE Profile (
    ID SERIAL PRIMARY KEY,  -- Use SERIAL for auto-increment in PostgreSQL
    Username VARCHAR(50) UNIQUE NOT NULL,
    Email VARCHAR(100) UNIQUE NOT NULL,
    PasswordHash VARCHAR(255) NOT NULL,
    Biography TEXT NULL,
    ProfileImagePath VARCHAR(255) NULL, 
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    LastModifiedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Function to auto-update LastModifiedAt
CREATE OR REPLACE FUNCTION update_last_modified()
RETURNS TRIGGER AS $$
BEGIN
    NEW.LastModifiedAt = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Table: Balance
CREATE TABLE Balance (
    ID INT PRIMARY KEY,
    Balance DECIMAL(10, 2) CHECK (Balance >= 0) DEFAULT 100,  -- Default Balance is 100
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    LastModifiedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ID) REFERENCES Profile(ID) ON DELETE CASCADE
);

-- Table: Transactions
CREATE TABLE Transactions (
    TransactionID SERIAL PRIMARY KEY,
    SenderID INT NOT NULL,
    ReceiverID INT NOT NULL,
    Amount DECIMAL(10, 2) NOT NULL CHECK (Amount > 0),
    Comment TEXT,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    LastModifiedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (SenderID) REFERENCES Profile(ID) ON DELETE CASCADE,
    FOREIGN KEY (ReceiverID) REFERENCES Profile(ID) ON DELETE CASCADE
);

-- Function to insert initial balance of 100 when a new profile is created
CREATE OR REPLACE FUNCTION insert_initial_balance()
RETURNS TRIGGER AS $$
BEGIN
    INSERT INTO Balance (ID, Balance) VALUES (NEW.ID, 100);
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Triggers to auto-update LastModifiedAt on UPDATE
CREATE TRIGGER update_profile_last_modified
BEFORE UPDATE ON Profile
FOR EACH ROW EXECUTE FUNCTION update_last_modified();

CREATE TRIGGER update_balance_last_modified
BEFORE UPDATE ON Balance
FOR EACH ROW EXECUTE FUNCTION update_last_modified();

CREATE TRIGGER update_transactions_last_modified
BEFORE UPDATE ON Transactions
FOR EACH ROW EXECUTE FUNCTION update_last_modified();

-- Trigger to automatically create a balance entry with 100 for new users
CREATE TRIGGER create_balance_on_profile_insert
AFTER INSERT ON Profile
FOR EACH ROW EXECUTE FUNCTION insert_initial_balance();

ALTER TABLE Balance ADD CONSTRAINT unique_balance_user UNIQUE (ID);