USE ContactManagerDB;

-- Seed users
INSERT INTO Users (FirstName, LastName, Login, Password)
VALUES
    ('John', 'Doe', 'johndoe', 'password1'),
    ('Jane', 'Smith', 'janesmith', 'password2');

-- Seed contacts for John (UserID = 1)
INSERT INTO Contacts (FirstName, LastName, EmailAddress, PhoneNumber, UserID)
VALUES
    ('Alice', 'Johnson', 'alice@example.com', '407-555-1001', 1),
    ('Bob', 'Williams', 'bob@example.com', '407-555-1002', 1);

-- Seed contacts for Jane (UserID = 2)
INSERT INTO Contacts (FirstName, LastName, EmailAddress, PhoneNumber, UserID)
VALUES
    ('Charlie', 'Brown', 'charlie@example.com', '407-555-2001', 2),
    ('Diana', 'Miller', 'diana@example.com', '407-555-2002', 2);