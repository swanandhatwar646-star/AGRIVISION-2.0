<?php
session_start();
require_once '../config/config.php';
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$db = new Database();

$stmt = $db->query("SELECT * FROM users WHERE id = ?");
$user = $db->fetch($stmt, [$_SESSION['user_id']]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kisan Credit Card - AGRIVISION</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h3><i class="fas fa-leaf"></i> AGRIVISION</h3>
            </div>
            <nav class="sidebar-menu">
                <a href="dashboard.php"><i class="fas fa-home"></i> <?php echo t('dashboard'); ?></a>
                <a href="my-field.php"><i class="fas fa-seedling"></i> <?php echo t('my_field'); ?></a>
                <a href="krishi-mandi.php"><i class="fas fa-store"></i> <?php echo t('krishi_mandi'); ?></a>
                <a href="my-greenhouse.php"><i class="fas fa-warehouse"></i> <?php echo t('my_greenhouse'); ?></a>
                <a href="ai-support.php"><i class="fas fa-robot"></i> <?php echo t('ai_support'); ?></a>
                <a href="appointments.php"><i class="fas fa-calendar"></i> <?php echo t('appointments'); ?></a>
                <a href="profile.php"><i class="fas fa-user"></i> <?php echo t('profile'); ?></a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> <?php echo t('logout'); ?></a>
            </nav>
        </aside>
        
        <main class="main-content">
            <div class="top-bar">
                <div class="user-info">
                    <div class="user-avatar"><?php echo strtoupper(substr($user['name'], 0, 1)); ?></div>
                    <div>
                        <h4><?php echo htmlspecialchars($user['name']); ?></h4>
                        <small>Farmer</small>
                    </div>
                </div>
                <div style="display: flex; gap: 15px; align-items: center;">
                    <a href="notifications.php" style="position: relative; color: var(--text-dark); text-decoration: none;">
                        <i class="fas fa-bell fa-lg"></i>
                    </a>
                    <button class="theme-toggle" title="Toggle Theme">
                        <i class="fas fa-moon"></i>
                    </button>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-credit-card"></i> Kisan Credit Card (KCC) Through Banks</h3>
                    <p style="font-size: 0.9rem; color: var(--text-light); margin: 0;">Complete guide for KCC application through various banks</p>
                </div>
                
                <div class="kcc-intro">
                    <div class="intro-card">
                        <div class="intro-icon">
                            <i class="fas fa-credit-card fa-3x"></i>
                        </div>
                        <div class="intro-content">
                            <h4>What is Kisan Credit Card?</h4>
                            <p>Kisan Credit Card (KCC) is a credit facility scheme for farmers to meet their agricultural credit needs. It provides timely and adequate credit support to farmers for cultivation and other needs.</p>
                        </div>
                    </div>
                </div>
                
                <div class="banks-grid">
                    <h4><i class="fas fa-university"></i> Banks Offering KCC</h4>
                    
                    <div class="bank-card">
                        <div class="bank-header">
                            <div class="bank-logo">
                                <i class="fas fa-landmark fa-2x" style="color: #0066b3;"></i>
                            </div>
                            <h5>State Bank of India (SBI)</h5>
                        </div>
                        <div class="bank-details">
                            <div class="feature-list">
                                <div class="feature-item">
                                    <i class="fas fa-check-circle text-success"></i>
                                    <span>Interest Rate: 7% - 9% per annum</span>
                                </div>
                                <div class="feature-item">
                                    <i class="fas fa-check-circle text-success"></i>
                                    <span>Loan Amount: Up to ₹3 lakh</span>
                                </div>
                                <div class="feature-item">
                                    <i class="fas fa-check-circle text-success"></i>
                                    <span>Processing Fee: Nil</span>
                                </div>
                            </div>
                            <div class="bank-actions">
                                <button class="btn btn-primary" onclick="showApplicationDetails('sbi')">
                                    <i class="fas fa-file-alt"></i> Apply Now
                                </button>
                                <a href="https://sbi.co.in" target="_blank" class="btn btn-outline">
                                    <i class="fas fa-external-link-alt"></i> Visit Bank
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bank-card">
                        <div class="bank-header">
                            <div class="bank-logo">
                                <i class="fas fa-landmark fa-2x" style="color: #0047ab;"></i>
                            </div>
                            <h5>Punjab National Bank (PNB)</h5>
                        </div>
                        <div class="bank-details">
                            <div class="feature-list">
                                <div class="feature-item">
                                    <i class="fas fa-check-circle text-success"></i>
                                    <span>Interest Rate: 7.5% - 9.5% per annum</span>
                                </div>
                                <div class="feature-item">
                                    <i class="fas fa-check-circle text-success"></i>
                                    <span>Loan Amount: Up to ₹5 lakh</span>
                                </div>
                                <div class="feature-item">
                                    <i class="fas fa-check-circle text-success"></i>
                                    <span>Processing Fee: ₹500</span>
                                </div>
                            </div>
                            <div class="bank-actions">
                                <button class="btn btn-primary" onclick="showApplicationDetails('pnb')">
                                    <i class="fas fa-file-alt"></i> Apply Now
                                </button>
                                <a href="https://pnbindia.in" target="_blank" class="btn btn-outline">
                                    <i class="fas fa-external-link-alt"></i> Visit Bank
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bank-card">
                        <div class="bank-header">
                            <div class="bank-logo">
                                <i class="fas fa-landmark fa-2x" style="color: #003087;"></i>
                            </div>
                            <h5>Bank of Baroda (BOB)</h5>
                        </div>
                        <div class="bank-details">
                            <div class="feature-list">
                                <div class="feature-item">
                                    <i class="fas fa-check-circle text-success"></i>
                                    <span>Interest Rate: 7.25% - 9.25% per annum</span>
                                </div>
                                <div class="feature-item">
                                    <i class="fas fa-check-circle text-success"></i>
                                    <span>Loan Amount: Up to ₹4 lakh</span>
                                </div>
                                <div class="feature-item">
                                    <i class="fas fa-check-circle text-success"></i>
                                    <span>Processing Fee: ₹250</span>
                                </div>
                            </div>
                            <div class="bank-actions">
                                <button class="btn btn-primary" onclick="showApplicationDetails('bob')">
                                    <i class="fas fa-file-alt"></i> Apply Now
                                </button>
                                <a href="https://bankofbaroda.in" target="_blank" class="btn btn-outline">
                                    <i class="fas fa-external-link-alt"></i> Visit Bank
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bank-card">
                        <div class="bank-header">
                            <div class="bank-logo">
                                <i class="fas fa-landmark fa-2x" style="color: #ff6600;"></i>
                            </div>
                            <h5>ICICI Bank</h5>
                        </div>
                        <div class="bank-details">
                            <div class="feature-list">
                                <div class="feature-item">
                                    <i class="fas fa-check-circle text-success"></i>
                                    <span>Interest Rate: 8% - 10% per annum</span>
                                </div>
                                <div class="feature-item">
                                    <i class="fas fa-check-circle text-success"></i>
                                    <span>Loan Amount: Up to ₹10 lakh</span>
                                </div>
                                <div class="feature-item">
                                    <i class="fas fa-check-circle text-success"></i>
                                    <span>Processing Fee: ₹1000</span>
                                </div>
                            </div>
                            <div class="bank-actions">
                                <button class="btn btn-primary" onclick="showApplicationDetails('icici')">
                                    <i class="fas fa-file-alt"></i> Apply Now
                                </button>
                                <a href="https://icicibank.com" target="_blank" class="btn btn-outline">
                                    <i class="fas fa-external-link-alt"></i> Visit Bank
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bank-card">
                        <div class="bank-header">
                            <div class="bank-logo">
                                <i class="fas fa-landmark fa-2x" style="color: #004d40;"></i>
                            </div>
                            <h5>HDFC Bank</h5>
                        </div>
                        <div class="bank-details">
                            <div class="feature-list">
                                <div class="feature-item">
                                    <i class="fas fa-check-circle text-success"></i>
                                    <span>Interest Rate: 8.5% - 10.5% per annum</span>
                                </div>
                                <div class="feature-item">
                                    <i class="fas fa-check-circle text-success"></i>
                                    <span>Loan Amount: Up to ₹8 lakh</span>
                                </div>
                                <div class="feature-item">
                                    <i class="fas fa-check-circle text-success"></i>
                                    <span>Processing Fee: ₹750</span>
                                </div>
                            </div>
                            <div class="bank-actions">
                                <button class="btn btn-primary" onclick="showApplicationDetails('hdfc')">
                                    <i class="fas fa-file-alt"></i> Apply Now
                                </button>
                                <a href="https://hdfcbank.com" target="_blank" class="btn btn-outline">
                                    <i class="fas fa-external-link-alt"></i> Visit Bank
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bank-card">
                        <div class="bank-header">
                            <div class="bank-logo">
                                <i class="fas fa-landmark fa-2x" style="color: #00695c;"></i>
                            </div>
                            <h5>Axis Bank</h5>
                        </div>
                        <div class="bank-details">
                            <div class="feature-list">
                                <div class="feature-item">
                                    <i class="fas fa-check-circle text-success"></i>
                                    <span>Interest Rate: 8% - 9.5% per annum</span>
                                </div>
                                <div class="feature-item">
                                    <i class="fas fa-check-circle text-success"></i>
                                    <span>Loan Amount: Up to ₹6 lakh</span>
                                </div>
                                <div class="feature-item">
                                    <i class="fas fa-check-circle text-success"></i>
                                    <span>Processing Fee: ₹500</span>
                                </div>
                            </div>
                            <div class="bank-actions">
                                <button class="btn btn-primary" onclick="showApplicationDetails('axis')">
                                    <i class="fas fa-file-alt"></i> Apply Now
                                </button>
                                <a href="https://axisbank.com" target="_blank" class="btn btn-outline">
                                    <i class="fas fa-external-link-alt"></i> Visit Bank
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="application-process">
                    <h4><i class="fas fa-tasks"></i> Application Process</h4>
                    <div class="process-steps">
                        <div class="step">
                            <div class="step-number">1</div>
                            <div class="step-content">
                                <h5>Choose Your Bank</h5>
                                <p>Select the bank that offers the best KCC terms for your needs</p>
                            </div>
                        </div>
                        <div class="step">
                            <div class="step-number">2</div>
                            <div class="step-content">
                                <h5>Gather Documents</h5>
                                <p>Collect all required documents (Aadhaar, PAN, Land records, etc.)</p>
                            </div>
                        </div>
                        <div class="step">
                            <div class="step-number">3</div>
                            <div class="step-content">
                                <h5>Submit Application</h5>
                                <p>Fill the KCC application form online or visit bank branch</p>
                            </div>
                        </div>
                        <div class="step">
                            <div class="step-number">4</div>
                            <div class="step-content">
                                <h5>Verification</h5>
                                <p>Bank will verify your documents and land records</p>
                            </div>
                        </div>
                        <div class="step">
                            <div class="step-number">5</div>
                            <div class="step-content">
                                <h5>Card Issuance</h5>
                                <p>Receive your KCC card and start using credit facility</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="documents-required">
                    <h4><i class="fas fa-file-alt"></i> Documents Required</h4>
                    <div class="documents-grid">
                        <div class="document-item">
                            <i class="fas fa-id-card fa-2x"></i>
                            <div>
                                <strong>Aadhaar Card</strong>
                                <p>Original and photocopy</p>
                            </div>
                        </div>
                        <div class="document-item">
                            <i class="fas fa-id-badge fa-2x"></i>
                            <div>
                                <strong>PAN Card</strong>
                                <p>Original and photocopy</p>
                            </div>
                        </div>
                        <div class="document-item">
                            <i class="fas fa-map fa-2x"></i>
                            <div>
                                <strong>Land Records</strong>
                                <p>7/12 extract, 8A, etc.</p>
                            </div>
                        </div>
                        <div class="document-item">
                            <i class="fas fa-camera fa-2x"></i>
                            <div>
                                <strong>Passport Photo</strong>
                                <p>Recent photographs</p>
                            </div>
                        </div>
                        <div class="document-item">
                            <i class="fas fa-file-invoice fa-2x"></i>
                            <div>
                                <strong>Income Proof</strong>
                                <p>Agricultural income documents</p>
                            </div>
                        </div>
                        <div class="document-item">
                            <i class="fas fa-home fa-2x"></i>
                            <div>
                                <strong>Address Proof</strong>
                                <p>Ration card, electricity bill</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <nav class="bottom-nav">
        <div class="container">
            <a href="dashboard.php">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            <a href="my-field.php">
                <i class="fas fa-seedling"></i>
                <span>My Field</span>
            </a>
            <a href="krishi-mandi.php">
                <i class="fas fa-store"></i>
                <span>Krishi Mandi</span>
            </a>
            <a href="profile.php">
                <i class="fas fa-user"></i>
                <span>Profile</span>
            </a>
        </div>
    </nav>
    
    <!-- Application Details Modal -->
    <div id="applicationModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h4 id="modalTitle">KCC Application Details</h4>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <div class="modal-body" id="modalBody">
                <!-- Content will be dynamically loaded -->
            </div>
        </div>
    </div>
    
    <style>
        .kcc-intro {
            margin-bottom: 30px;
        }
        
        .intro-card {
            display: flex;
            align-items: center;
            gap: 20px;
            background: var(--bg-light);
            padding: 25px;
            border-radius: var(--radius);
            border: 1px solid var(--border-color);
        }
        
        .intro-icon {
            color: var(--primary-color);
            flex-shrink: 0;
        }
        
        .intro-content h4 {
            margin: 0 0 10px 0;
            color: var(--text-dark);
        }
        
        .banks-grid {
            margin-bottom: 30px;
        }
        
        .banks-grid h4 {
            margin-bottom: 20px;
            color: var(--text-dark);
        }
        
        .bank-card {
            background: var(--bg-light);
            border-radius: var(--radius);
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid var(--border-color);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .bank-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .bank-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .bank-logo {
            width: 60px;
            height: 60px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid var(--border-color);
        }
        
        .bank-header h5 {
            margin: 0;
            color: var(--text-dark);
            font-size: 1.2rem;
        }
        
        .feature-list {
            margin-bottom: 20px;
        }
        
        .feature-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
            font-size: 0.95rem;
        }
        
        .bank-actions {
            display: flex;
            gap: 10px;
        }
        
        .bank-actions .btn {
            flex: 1;
        }
        
        .application-process {
            margin-bottom: 30px;
        }
        
        .application-process h4 {
            margin-bottom: 20px;
            color: var(--text-dark);
        }
        
        .process-steps {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .step {
            display: flex;
            align-items: flex-start;
            gap: 15px;
        }
        
        .step-number {
            width: 40px;
            height: 40px;
            background: var(--primary-color);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            flex-shrink: 0;
        }
        
        .step-content h5 {
            margin: 0 0 5px 0;
            color: var(--text-dark);
        }
        
        .step-content p {
            margin: 0;
            color: var(--text-light);
            font-size: 0.9rem;
        }
        
        .documents-required h4 {
            margin-bottom: 20px;
            color: var(--text-dark);
        }
        
        .documents-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .document-item {
            display: flex;
            align-items: center;
            gap: 15px;
            background: var(--bg-light);
            padding: 20px;
            border-radius: var(--radius);
            border: 1px solid var(--border-color);
        }
        
        .document-item i {
            color: var(--primary-color);
            flex-shrink: 0;
        }
        
        .document-item strong {
            display: block;
            color: var(--text-dark);
            margin-bottom: 5px;
        }
        
        .document-item p {
            margin: 0;
            color: var(--text-light);
            font-size: 0.9rem;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: var(--bg-color);
            margin: 5% auto;
            padding: 0;
            border-radius: var(--radius);
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .modal-header h4 {
            margin: 0;
            color: var(--text-dark);
        }
        
        .close {
            color: var(--text-light);
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: var(--text-dark);
        }
        
        .modal-body {
            padding: 20px;
        }
        
        @media (max-width: 768px) {
            .intro-card {
                flex-direction: column;
                text-align: center;
            }
            
            .process-steps {
                grid-template-columns: 1fr;
            }
            
            .documents-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
    
    <script>
        function showApplicationDetails(bank) {
            const modal = document.getElementById('applicationModal');
            const modalTitle = document.getElementById('modalTitle');
            const modalBody = document.getElementById('modalBody');
            
            const bankDetails = {
                'sbi': {
                    title: 'SBI KCC Application',
                    content: `
                        <h5>How to Apply for SBI KCC:</h5>
                        <ol>
                            <li>Visit your nearest SBI branch</li>
                            <li>Fill the KCC application form</li>
                            <li>Submit required documents</li>
                            <li>Bank will verify and process your application</li>
                        </ol>
                        <h5>Online Application:</h5>
                        <p>You can also apply online through SBI YONO app or website</p>
                        <h5>Contact:</h5>
                        <p>Phone: 1800-1234 (Toll Free)</p>
                        <p>Email: kcc@sbi.co.in</p>
                    `
                },
                'pnb': {
                    title: 'PNB KCC Application',
                    content: `
                        <h5>How to Apply for PNB KCC:</h5>
                        <ol>
                            <li>Visit PNB branch with documents</li>
                            <li>Complete application form</li>
                            <li>Submit land ownership proof</li>
                            <li>Wait for verification and approval</li>
                        </ol>
                        <h5>Online Application:</h5>
                        <p>Apply through PNB ONE app or website</p>
                        <h5>Contact:</h5>
                        <p>Phone: 1800-180-2222 (Toll Free)</p>
                        <p>Email: kcc@pnb.co.in</p>
                    `
                },
                'bob': {
                    title: 'Bank of Baroda KCC Application',
                    content: `
                        <h5>How to Apply for BOB KCC:</h5>
                        <ol>
                            <li>Visit Bank of Baroda branch</li>
                            <li>Submit application with documents</li>
                            <li>Provide land records</li>
                            <li>Complete verification process</li>
                        </ol>
                        <h5>Online Application:</h5>
                        <p>Apply through BOB World app or website</p>
                        <h5>Contact:</h5>
                        <p>Phone: 1800-223-444 (Toll Free)</p>
                        <p>Email: kcc@bankofbaroda.co.in</p>
                    `
                },
                'icici': {
                    title: 'ICICI Bank KCC Application',
                    content: `
                        <h5>How to Apply for ICICI KCC:</h5>
                        <ol>
                            <li>Visit ICICI Bank branch</li>
                            <li>Fill KCC application form</li>
                            <li>Submit all required documents</li>
                            <li>Complete verification process</li>
                        </ol>
                        <h5>Online Application:</h5>
                        <p>Apply through iMobile Pay app or website</p>
                        <h5>Contact:</h5>
                        <p>Phone: 1800-103-8181 (Toll Free)</p>
                        <p>Email: kcc@icicibank.com</p>
                    `
                },
                'hdfc': {
                    title: 'HDFC Bank KCC Application',
                    content: `
                        <h5>How to Apply for HDFC KCC:</h5>
                        <ol>
                            <li>Visit HDFC Bank branch</li>
                            <li>Complete application process</li>
                            <li>Submit documents and land proof</li>
                            <li>Wait for approval</li>
                        </ol>
                        <h5>Online Application:</h5>
                        <p>Apply through HDFC Bank app or website</p>
                        <h5>Contact:</h5>
                        <p>Phone: 1800-266-9060 (Toll Free)</p>
                        <p>Email: kcc@hdfcbank.com</p>
                    `
                },
                'axis': {
                    title: 'Axis Bank KCC Application',
                    content: `
                        <h5>How to Apply for Axis KCC:</h5>
                        <ol>
                            <li>Visit Axis Bank branch</li>
                            <li>Submit application with documents</li>
                            <li>Provide land ownership proof</li>
                            <li>Complete verification</li>
                        </ol>
                        <h5>Online Application:</h5>
                        <p>Apply through Axis Mobile app or website</p>
                        <h5>Contact:</h5>
                        <p>Phone: 1800-419-8158 (Toll Free)</p>
                        <p>Email: kcc@axisbank.com</p>
                    `
                }
            };
            
            const details = bankDetails[bank];
            modalTitle.textContent = details.title;
            modalBody.innerHTML = details.content;
            
            modal.style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('applicationModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('applicationModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>
