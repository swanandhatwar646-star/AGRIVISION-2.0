<!DOCTYPE html>
<html>
<head>
    <title>Create Government Schemes Tables</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .info { color: #0066cc; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>Create Government Schemes Tables</h1>
    
    <?php
    // Database configuration
    $host = 'localhost';
    $username = 'root';
    $password = '';
    $database = 'agrivision_db';

    try {
        // Connect to MySQL
        $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        echo "<div class='info'>Connected to database: $database</div>";
        
        // SQL statements to create tables
        $sql_statements = [
            // Create government_schemes table
            "CREATE TABLE IF NOT EXISTS government_schemes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                description TEXT NOT NULL,
                category VARCHAR(100) DEFAULT 'Agriculture',
                subsidy VARCHAR(100),
                last_date DATE,
                eligibility TEXT,
                application_link VARCHAR(500),
                status ENUM('active', 'inactive') DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )",
            
            // Create scheme_applications table
            "CREATE TABLE IF NOT EXISTS scheme_applications (
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
            )"
        ];
        
        // Execute table creation
        foreach ($sql_statements as $sql) {
            try {
                $pdo->exec($sql);
                echo "<div class='success'>✅ Table created successfully</div>";
            } catch (PDOException $e) {
                echo "<div class='error'>❌ Error creating table: " . $e->getMessage() . "</div>";
            }
        }
        
        // Check if tables exist
        $stmt = $pdo->query("SHOW TABLES LIKE 'government_schemes'");
        if ($stmt->rowCount() > 0) {
            echo "<div class='success'>✅ government_schemes table exists</div>";
        } else {
            echo "<div class='error'>❌ government_schemes table not found</div>";
        }
        
        $stmt = $pdo->query("SHOW TABLES LIKE 'scheme_applications'");
        if ($stmt->rowCount() > 0) {
            echo "<div class='success'>✅ scheme_applications table exists</div>";
        } else {
            echo "<div class='error'>❌ scheme_applications table not found</div>";
        }
        
        // Insert sample data if tables are empty
        $stmt = $pdo->query("SELECT COUNT(*) FROM government_schemes");
        $count = $stmt->fetchColumn();
        
        if ($count == 0) {
            // Sample government schemes
            $schemes = [
                [
                    'title' => 'Pradhan Mantri Kisan Samman Nidhi (PM-KISAN)',
                    'description' => 'Income support scheme for small and marginal farmers. Direct cash transfer of ₹6,000 per year in three equal installments.',
                    'category' => 'Income Support',
                    'subsidy' => '₹6,000 per year',
                    'last_date' => '2024-12-31',
                    'eligibility' => 'Small and marginal farmers with landholding up to 2 hectares',
                    'application_link' => 'https://pmkisan.gov.in/'
                ],
                [
                    'title' => 'Pradhan Mantri Fasal Bima Yojana (PMFBY)',
                    'description' => 'Crop insurance scheme to provide comprehensive insurance coverage against crop loss due to natural calamities.',
                    'category' => 'Insurance',
                    'subsidy' => 'Premium subsidy up to 90%',
                    'last_date' => '2024-11-30',
                    'eligibility' => 'All farmers growing notified crops in notified areas',
                    'application_link' => 'https://pmfby.gov.in/'
                ],
                [
                    'title' => 'Soil Health Card Scheme',
                    'description' => 'Issue of soil health cards to farmers once every 2 years to provide information on soil nutrient status.',
                    'category' => 'Soil Management',
                    'subsidy' => 'Free soil testing',
                    'last_date' => '2024-10-31',
                    'eligibility' => 'All farmers',
                    'application_link' => 'https://soilhealth.dac.gov.in/'
                ],
                [
                    'title' => 'Paramparagat Krishi Vikas Yojana (PKVY)',
                    'description' => 'Promotion of organic farming and traditional agriculture practices.',
                    'category' => 'Organic Farming',
                    'subsidy' => '₹50,000 per hectare',
                    'last_date' => '2024-09-30',
                    'eligibility' => 'Farmers adopting organic farming',
                    'application_link' => 'https://pgsindia-ncof.gov.in/'
                ],
                [
                    'title' => 'National Mission on Agricultural Extension and Technology',
                    'description' => 'Promote agricultural extension and technology dissemination.',
                    'category' => 'Technology',
                    'subsidy' => 'Varies by technology',
                    'last_date' => '2024-08-31',
                    'eligibility' => 'All farmers',
                    'application_link' => 'https://agrimission.nic.in/'
                ],
                [
                    'title' => 'Agricultural Infrastructure Fund',
                    'description' => 'Financing facility for investment in agricultural infrastructure projects.',
                    'category' => 'Infrastructure',
                    'subsidy' => '3% interest subvention',
                    'last_date' => '2024-07-31',
                    'eligibility' => 'Farmers, FPOs, and agri-entrepreneurs',
                    'application_link' => 'https://agriinfra.nabard.org/'
                ]
            ];
            
            // Insert schemes
            $insert_sql = "INSERT INTO government_schemes (title, description, category, subsidy, last_date, eligibility, application_link) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($insert_sql);
            
            foreach ($schemes as $scheme) {
                $stmt->execute([
                    $scheme['title'],
                    $scheme['description'],
                    $scheme['category'],
                    $scheme['subsidy'],
                    $scheme['last_date'],
                    $scheme['eligibility'],
                    $scheme['application_link']
                ]);
            }
            
            echo "<div class='success'>✅ Inserted " . count($schemes) . " government schemes</div>";
        } else {
            echo "<div class='info'>ℹ️ Found $count existing government schemes</div>";
        }
        
        // Final verification
        echo "<h2>Database Status:</h2>";
        
        $tables = ['government_schemes', 'scheme_applications'];
        foreach ($tables as $table) {
            $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
            $count = $stmt->fetchColumn();
            echo "<div class='info'>$table: $count records</div>";
        }
        
        echo "<div class='success'><h2>✅ Database setup completed successfully!</h2></div>";
        echo "<p><a href='user/government-schemes.php'>Go to Government Schemes Page</a></p>";
        
    } catch (PDOException $e) {
        echo "<div class='error'>❌ Database Error: " . $e->getMessage() . "</div>";
        echo "<div class='info'>Please check your database connection details:</div>";
        echo "<pre>
Host: $host
Database: $database
Username: $username
Password: " . (empty($password) ? '(empty)' : '***') . "
        </pre>";
    }
    ?>
    
</body>
</html>
