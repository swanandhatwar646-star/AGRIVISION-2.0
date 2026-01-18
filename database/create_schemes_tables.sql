-- Government Schemes Table
CREATE TABLE government_schemes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    category VARCHAR(100) DEFAULT 'Agriculture',
    subsidy VARCHAR(100),
    last_date DATE,
    eligibility TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Scheme Applications Table
CREATE TABLE scheme_applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    scheme_id INT NOT NULL,
    application_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    reference_id VARCHAR(50),
    notes TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (scheme_id) REFERENCES government_schemes(id) ON DELETE CASCADE,
    UNIQUE KEY unique_application (user_id, scheme_id)
);

-- Insert sample government schemes
INSERT INTO government_schemes (title, description, category, subsidy, last_date, eligibility) VALUES
('Pradhan Mantri Kisan Samman Nidhi (PM-KISAN)', 'Income support scheme for small and marginal farmers. Direct cash transfer of ₹6,000 per year in three equal installments.', 'Income Support', '₹6,000 per year', '2024-12-31', 'Small and marginal farmers with landholding up to 2 hectares'),
('Pradhan Mantri Fasal Bima Yojana (PMFBY)', 'Crop insurance scheme to provide comprehensive insurance coverage against crop loss due to natural calamities.', 'Insurance', 'Premium subsidy up to 90%', '2024-11-30', 'All farmers growing notified crops in notified areas'),
('Soil Health Card Scheme', 'Issue of soil health cards to farmers once every 2 years to provide information on soil nutrient status.', 'Soil Management', 'Free soil testing', '2024-10-31', 'All farmers'),
('Paramparagat Krishi Vikas Yojana (PKVY)', 'Promotion of organic farming and traditional agriculture practices.', 'Organic Farming', '₹50,000 per hectare', '2024-09-30', 'Farmers adopting organic farming'),
('National Mission on Agricultural Extension and Technology', 'Promote agricultural extension and technology dissemination.', 'Technology', 'Varies by technology', '2024-08-31', 'All farmers'),
('Agricultural Infrastructure Fund', 'Financing facility for investment in agricultural infrastructure projects.', 'Infrastructure', '3% interest subvention', '2024-07-31', 'Farmers, FPOs, and agri-entrepreneurs');
