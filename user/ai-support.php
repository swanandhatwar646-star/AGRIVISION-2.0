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

$response = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $query = trim($_POST['query'] ?? '');
    
    if (!empty($query)) {
        $query_type = 'general';
        
        if (stripos($query, 'crop') !== false || stripos($query, 'plant') !== false) {
            $query_type = 'crop_health';
        } elseif (stripos($query, 'fertilizer') !== false || stripos($query, 'nutrient') !== false) {
            $query_type = 'fertilizer';
        } elseif (stripos($query, 'pest') !== false || stripos($query, 'insect') !== false) {
            $query_type = 'pest';
        } elseif (stripos($query, 'weather') !== false || stripos($query, 'rain') !== false || stripos($query, 'temperature') !== false) {
            $query_type = 'weather';
        }
        
        $ai_response = generateAIResponse($query, $query_type);
        
        $stmt = $db->query("INSERT INTO ai_queries (user_id, query, response, query_type) VALUES (?, ?, ?, ?)");
        $db->execute($stmt, [$_SESSION['user_id'], $query, $ai_response, $query_type]);
        
        $response = $ai_response;
    }
}

$stmt = $db->query("SELECT * FROM ai_queries WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$history = $db->fetchAll($stmt, [$_SESSION['user_id']]);

function generateAIResponse($query, $type) {
    $query_lower = strtolower($query);
    
    // Enhanced Crop Health responses
    if ($type == 'crop_health') {
        if (stripos($query, 'yellow') !== false || stripos($query, 'wilting') !== false) {
            return "🌾 **Yellowing or Wilting Leaves**\n\n**Possible Causes:**\n• **Water Stress** - Check soil moisture. Underwatering causes wilting, overwatering causes yellowing.\n• **Nitrogen Deficiency** - Yellow leaves indicate lack of nitrogen.\n• **Nutrient Imbalance** - Excess of some nutrients can cause yellowing.\n• **Disease** - Fungal infections, root rot, or viral diseases.\n\n**Solutions:**\n1. **Check Soil Moisture** - Use moisture meter or finger test (2 inches deep)\n2. **Apply Fertilizer** - Use balanced NPK (20-20-20) if nitrogen deficient\n3. **Improve Drainage** - Ensure proper drainage to prevent root rot\n4. **Remove Affected Leaves** - Prune yellow leaves to prevent spread\n\n**Prevention:**\n• Maintain consistent watering schedule\n• Use organic mulch to retain moisture\n• Test soil pH (ideal: 6.0-7.0)";
        } elseif (stripos($query, 'growth') !== false || stripos($query, 'slow') !== false) {
            return "🌱 **Slow Crop Growth Analysis**\n\n**Common Reasons:**\n• **Insufficient Light** - Most crops need 6-8 hours direct sunlight\n• **Poor Soil** - Compacted soil prevents root development\n• **Nutrient Deficiency** - Lack of NPK (Nitrogen, Phosphorus, Potassium)\n• **Wrong Planting Time** - Seasonal timing affects growth rate\n• **Water Issues** - Both over and under watering\n\n**Quick Solutions:**\n1. **Increase Sunlight** - Trim nearby plants, use reflective mulch\n2. **Soil Aeration** - Loosen soil, add compost\n3. **Balanced Fertilizer** - Apply NPK based on soil test\n4. **Proper Watering** - Water deeply but infrequently\n5. **Seasonal Planning** - Plant according to crop calendar\n\n**Expected Growth Rates:**\n• Leafy vegetables: 4-6 weeks to harvest\n• Root vegetables: 8-12 weeks\n• Fruiting crops: 10-16 weeks";
        } elseif (stripos($query, 'pest') !== false || stripos($query, 'insect') !== false) {
            return "🐛 **Pest Management Guide**\n\n**Common Pests & Solutions:**\n\n**1. Aphids**\n• **Damage:** Yellow leaves, sticky honeydew, curled leaves\n• **Solution:** Neem oil spray, soapy water, ladybugs\n• **Prevention:** Avoid excess nitrogen, companion planting\n\n**2. Whiteflies**\n• **Damage:** Yellow mottling, stunted growth\n• **Solution:** Yellow sticky traps, reflective mulch\n• **Prevention:** Remove weeds, good air circulation\n\n**3. Spider Mites**\n• **Damage:** Yellow speckling, fine webbing\n• **Solution:** Increase humidity, predatory mites\n• **Prevention:** Regular misting, dust control\n\n**4. Caterpillars**\n• **Damage:** Chewed leaves, holes in foliage\n• **Solution:** Hand picking, BT spray, neem oil\n• **Prevention:** Row covers, beneficial insects\n\n**IPM Strategy:**\n1. **Monitor** - Weekly field inspections\n2. **Identify** - Use pest identification apps\n3. **Threshold** - Treat only when economic damage occurs\n4. **Control** - Start with least toxic methods\n5. **Evaluate** - Assess control effectiveness";
        } elseif (stripos($query, 'disease') !== false || stripos($query, 'fungus') !== false) {
            return "🦠 **Disease Management Guide**\n\n**Common Diseases & Treatment:**\n\n**1. Powdery Mildew**\n• **Symptoms:** White powdery coating on leaves\n• **Treatment:** Sulfur spray, neem oil, improve air flow\n• **Prevention:** Proper spacing, resistant varieties\n\n**2. Downy Mildew**\n• **Symptoms:** Yellow spots on upper leaf surface\n• **Treatment:** Copper spray, reduce humidity\n• **Prevention:** Avoid overhead watering\n\n**3. Root Rot**\n• **Symptoms:** Wilting, brown roots, plant death\n• **Treatment:** Improve drainage, fungicide drench\n• **Prevention:** Well-draining soil, avoid overwatering\n\n**4. Leaf Spot**\n• **Symptoms:** Brown/black spots on leaves\n• **Treatment:** Remove affected leaves, copper spray\n• **Prevention:** Crop rotation, resistant varieties\n\n**General Prevention:**\n• Use certified disease-free seeds\n• Practice crop rotation (3-4 year cycle)\n• Maintain proper plant spacing\n• Remove and destroy infected plants";
        }
        return "🌾 **Crop Health Support**\n\n**I can help with:**\n• **Disease Diagnosis** - Describe symptoms for identification\n• **Pest Problems** - Describe damage for solutions\n• **Growth Issues** - Explain growing conditions for help\n• **Nutrient Deficiencies** - Yellow leaves, stunting, poor yield\n• **Water Management** - Over/under watering issues\n\n**Monitoring Tips:**\n• Check plants daily for early problem detection\n• Keep records of treatments and results\n• Use My Field section for zone analysis\n• Test soil regularly for nutrient levels\n\n**Ask me about specific symptoms for targeted advice!**";
    }
    
    // Enhanced Fertilizer responses
    if ($type == 'fertilizer') {
        if (stripos($query, 'nitrogen') !== false) {
            return "🧪 **Nitrogen (N) Fertilizer Guide**\n\n**Role in Plants:**\n• Promotes leaf and stem growth\n• Essential for chlorophyll production\n• Improves protein content\n\n**Deficiency Signs:**\n• Yellowing of older leaves\n• Stunted growth\n• Poor yield\n\n**Best Sources:**\n• **Organic:** Compost, manure, blood meal, fish emulsion\n• **Chemical:** Urea (46-0-0), Ammonium sulfate (21-0-0)\n\n**Application Guidelines:**\n• **Timing:** Apply during active growth phase\n• **Rate:** 50-100 lbs/acre depending on crop\n• **Method:** Side-dress for established plants\n• **Caution:** Avoid over-application (causes burning)\n\n**Crop-Specific Needs:**\n• Leafy vegetables: High nitrogen needed\n• Fruiting crops: Moderate nitrogen\n• Root crops: Low to moderate";
        } elseif (stripos($query, 'phosphorus') !== false) {
            return "🦴 **Phosphorus (P) Fertilizer Guide**\n\n**Role in Plants:**\n• Essential for root development\n• Promotes flowering and fruiting\n• Energy transfer within plant\n\n**Deficiency Signs:**\n• Purple or reddish leaves\n• Poor root growth\n• Delayed maturity\n• Weak stems\n\n**Best Sources:**\n• **Organic:** Bone meal, rock phosphate, fish bone meal\n• **Chemical:** DAP (18-46-0), Super phosphate (0-46-0)\n\n**Application Guidelines:**\n• **Timing:** Best applied at planting time\n• **Rate:** 40-80 lbs/acre based on soil test\n• **Method:** Incorporate into soil before planting\n• **Placement:** Band application near root zone\n\n**Crop Benefits:**\n• Strong root system development\n• Earlier flowering\n• Better fruit set\n• Improved stress tolerance";
        } elseif (stripos($query, 'potassium') !== false) {
            return "⚡ **Potassium (K) Fertilizer Guide**\n\n**Role in Plants:**\n• Improves disease resistance\n• Enhances fruit quality and size\n• Regulates water movement\n• Strengthens stems\n\n**Deficiency Signs:**\n• Yellow/brown leaf edges\n• Weak stems\n• Poor fruit quality\n• Reduced yield\n\n**Best Sources:**\n• **Organic:** Wood ash, kelp meal, greensand\n• **Chemical:** Potassium chloride (0-0-60), Potassium sulfate (0-0-50)\n\n**Application Guidelines:**\n• **Timing:** Apply before flowering and fruit development\n• **Rate:** 40-80 lbs/acre depending on crop\n• **Method:** Broadcast and incorporate\n• **Caution:** Avoid contact with seeds/seedlings\n\n**Crop Benefits:**\n• Better disease resistance\n• Improved fruit quality\n• Stronger plant structure\n• Enhanced drought tolerance";
        } elseif (stripos($query, 'organic') !== false || stripos($query, 'natural') !== false) {
            return "🌿 **Organic Fertilizer Guide**\n\n**Types & Benefits:**\n\n**1. Compost**\n• **Nutrients:** Balanced NPK + micronutrients\n• **Benefits:** Improves soil structure, slow release\n• **Application:** 2-4 inches annually\n\n**2. Manure**\n• **Types:** Cow, chicken, horse\n• **Nutrients:** High in nitrogen, organic matter\n• **Application:** Well-aged, 1-2 inches\n\n**3. Green Manure**\n• **Crops:** Legumes (clover, vetch)\n• **Benefits:** Nitrogen fixation, soil improvement\n• **Application:** Incorporate 3-4 weeks before planting\n\n**4. Bone Meal**\n• **Nutrients:** High phosphorus, calcium\n• **Benefits:** Root development, flowering\n• **Application:** 1-2 cups per 100 sq ft\n\n**5. Fish Emulsion**\n• **Nutrients:** Balanced NPK, micronutrients\n• **Benefits:** Quick acting, foliar feed\n• **Application:** Dilute 1:4 with water\n\n**Organic Schedule:**\n• **Spring:** Apply compost before planting\n• **Growing:** Side-dress with compost monthly\n• **Fall:** Apply manure for winter protection";
        }
        return "🧪 **Complete Fertilizer Guide**\n\n**NPK Breakdown:**\n\n**Nitrogen (N)** - 🌿 **Leaf & Stem Growth**\n• Promotes vegetative growth\n• Essential for chlorophyll\n• Increases protein content\n\n**Phosphorus (P)** - 🦴 **Root & Flower Development**\n• Strong root systems\n• Early flowering\n• Energy transfer\n\n**Potassium (K)** - ⚡ **Fruit Quality & Disease Resistance**\n• Better fruit size and quality\n• Stronger plant structure\n• Improved stress tolerance\n\n**Application Timing:**\n• **Pre-planting:** Incorporate P and K into soil\n• **Early growth:** Focus on N for vegetative growth\n• **Flowering:** Reduce N, maintain P and K\n• **Fruiting:** Balanced NPK for fruit development\n\n**Best Practices:**\n• Soil test before application\n• Follow crop-specific recommendations\n• Split applications for better efficiency\n• Consider organic options for sustainable farming";
    }
    
    // Enhanced Pest responses
    if ($type == 'pest') {
        if (stripos($query, 'aphid') !== false) {
            return "🐛 **Aphid Control Guide**\n\n**Identification:**\n• Small, pear-shaped insects (1-10mm)\n• Colors: Green, black, yellow, pink\n• Found in clusters on new growth\n\n**Damage:**\n• Sap sucking causes yellowing, curling\n• Honeydew secretion leads to mold\n• Transmits plant viruses\n• Stunts plant growth\n\n**Control Methods:**\n\n**1. Natural Solutions:**\n• **Neem Oil** - 2% solution, weekly application\n• **Soap Spray** - 1 tsp soap per liter water\n• **Beneficial Insects** - Ladybugs, lacewings\n• **Companion Planting** - Garlic, onions, marigolds\n\n**2. Chemical Options:**\n• **Imidacloprid** - Systemic, long-lasting\n• **Pyrethrin** - Contact, organic option\n• **Insecticidal Soap** - Safe for edible crops\n\n**Prevention:**\n• Monitor plants twice weekly\n• Remove ant colonies (they farm aphids)\n• Avoid excess nitrogen fertilizer\n• Maintain plant diversity";
        } elseif (stripos($query, 'whitefly') !== false) {
            return "🪰 **Whitefly Control Guide**\n\n**Identification:**\n• Tiny white moth-like insects (1-2mm)\n• Fly up when plants are disturbed\n• Found on underside of leaves\n\n**Damage:**\n• Sap sucking causes yellowing\n• Honeydew leads to sooty mold\n• Transmits viruses\n• Reduces photosynthesis\n\n**Control Methods:**\n\n**1. Physical Control:**\n• **Yellow Sticky Traps** - Monitor adult populations\n• **Reflective Mulch** - Confuses and repels\n• **Vacuum** - For small infestations\n\n**2. Biological Control:**\n• **Encarsia Formosa** - Parasitic wasp\n• **Delphastus Catalinae** - Predatory beetle\n• **Beauveria Bassiana** - Fungal pathogen\n\n**3. Chemical Control:**\n• **Imidacloprid** - Systemic insecticide\n• **Pyriproxyfen** - Insect growth regulator\n• **Spinosad** - Natural insecticide\n\n**Prevention:**\n• Quarantine new plants for 2 weeks\n• Remove weeds that host whiteflies\n• Use fine mesh screens in greenhouses";
        }
        return "🐛 **Integrated Pest Management (IPM)**\n\n**IPM Strategy:**\n\n**1. Prevention (70% effort)**\n• Use resistant crop varieties\n• Practice crop rotation (3-4 years)\n• Maintain proper plant spacing\n• Keep fields clean\n• Use beneficial insects\n\n**2. Monitoring (20% effort)**\n• Weekly field inspections\n• Use pheromone traps\n• Scout for early signs\n• Keep detailed records\n\n**3. Control (10% effort)**\n• Economic threshold approach\n• Start with least toxic options\n• Target specific life stages\n• Rotate control methods\n\n**Common Pests by Crop:**\n• **Tomatoes:** Hornworms, whiteflies, aphids\n• **Cabbage:** Cabbage worms, aphids, flea beetles\n• **Corn:** Corn borers, earworms, cutworms\n• **Rice:** Stem borers, brown planthopper\n\n**Emergency Contacts:**\n• Local agricultural extension office\n• Plant pathology laboratory\n• Organic farming associations";
    }
    
    // Enhanced Weather responses
    if ($type == 'weather') {
        if (stripos($query, 'drought') !== false || stripos($query, 'dry') !== false) {
            return "🏜️ **Drought Management Guide**\n\n**Water Conservation:**\n\n**1. Irrigation Efficiency:**\n• **Drip Irrigation** - 90% efficiency vs 50% flood\n• **Mulching** - Reduces evaporation by 70%\n• **Timing** - Water early morning/late evening\n• **Soil Moisture** - Use sensors or tensiometers\n\n**2. Drought-Resistant Practices:**\n• **Deep Tillage** - Breaks hardpan for root penetration\n• **Conservation Tillage** - Leaves crop residue\n• **Cover Crops** - Protects soil from erosion\n• **Windbreaks** - Reduces water loss\n\n**3. Crop Selection:**\n• **Sorghum** - Most drought tolerant\n• **Millet** - Quick maturing, low water needs\n• **Cowpea** - Nitrogen fixing, drought tolerant\n• **Pigeon Pea** - Deep rooting, resilient\n\n**4. Soil Management:**\n• **Organic Matter** - Increases water holding capacity\n• **No-Till** - Preserves soil moisture\n• **Contour Planting** - Reduces runoff\n\n**Emergency Actions:**\n• Reduce plant population density\n• Apply anti-transpirants\n• Prioritize high-value crops";
        } elseif (stripos($query, 'rain') !== false || stripos($query, 'monsoon') !== false) {
            return "🌧️ **Rain Management Guide**\n\n**Heavy Rain Preparation:**\n\n**1. Field Drainage:**\n• **Raised Beds** - 15-20 cm high\n• **Contour Planting** - Follow land contours\n• **Drainage Ditches** - Remove excess water\n• **Grassed Waterways** - Prevent erosion\n\n**2. Soil Protection:**\n• **Cover Crops** - Prevent soil erosion\n• **Mulching** - Protects soil structure\n• **No-Till** - Maintains soil aggregates\n• **Windbreaks** - Reduces water impact\n\n**3. Crop Protection:**\n• **Resistant Varieties** - Waterlogging tolerant\n• **Proper Spacing** - Improves air circulation\n• **Staking/Support** - Prevents lodging\n• **Fungicide** - Prevent post-rain diseases\n\n**Post-Rain Actions:**\n• Assess waterlogging damage\n• Apply foliar nutrients if needed\n• Monitor for disease outbreaks\n• Plan for replanting if necessary";
        } elseif (stripos($query, 'temperature') !== false || stripos($query, 'heat') !== false) {
            return "🌡️ **Temperature Management Guide**\n\n**Heat Stress Management:**\n\n**1. Irrigation:**\n• **Increase Frequency** - Short, frequent watering\n• **Evaporative Cooling** - Misting during peak heat\n• **Night Watering** - Reduces plant stress\n• **Soil Temperature** - Mulch to keep roots cool\n\n**2. Crop Protection:**\n• **Shade Cloth** - 30-50% shade during extreme heat\n• **Anti-Transpirants** - Reduce water loss\n• **Windbreaks** - Reduce desiccation\n• **Heat-Tolerant Varieties** - Choose appropriate cultivars\n\n**3. Timing Adjustments:**\n• **Early Planting** - Avoid peak heat periods\n• **Evening Harvesting** - Reduce heat stress\n• **Reduced Fertilizer** - Lower during extreme heat\n\n**Temperature Guidelines by Crop:**\n• **Tomatoes:** Optimal 21-24°C, stress above 32°C\n• **Rice:** Optimal 25-35°C, stress above 38°C\n• **Wheat:** Optimal 15-20°C, stress above 30°C\n• **Cotton:** Optimal 25-30°C, stress above 35°C";
        }
        return "🌤️ **Weather Impact on Farming**\n\n**Key Weather Factors:**\n\n**1. Temperature**\n• **Optimal Range:** 20-30°C for most crops\n• **Frost Risk:** Below 10°C damages sensitive crops\n• **Heat Stress:** Above 35°C reduces yield\n• **Growing Degree Days:** Track for harvest timing\n\n**2. Rainfall**\n• **Optimal:** 25-35mm per week for most crops\n• **Deficit:** Below 20mm requires irrigation\n• **Excess:** Above 50mm causes waterlogging\n• **Distribution:** Even distribution more important than total\n\n**3. Humidity**\n• **High (>80%):** Promotes fungal diseases\n• **Low (<40%):** Increases water requirements\n• **Optimal:** 60-70% for most crops\n\n**4. Wind**\n• **Strong Winds:** Cause physical damage, increase evaporation\n• **Protection:** Windbreaks, shelterbelts\n• **Pollination:** Affects insect activity\n\n**Farming Adaptations:**\n• **Seasonal Planning:** Align planting with weather patterns\n• **Crop Selection:** Choose climate-appropriate varieties\n• **Irrigation Scheduling:** Based on weather forecasts\n• **Risk Management:** Insurance, diversification\n\n**Monitoring Tools:**\n• Weather stations on farm\n• Mobile apps for forecasts\n• Satellite data for large areas\n• Local agricultural advisories";
    }
    
    // Enhanced general responses
    return "🤖 **AGRIVISION AI Assistant**\n\n**I can help you with:**\n\n🌱 **Crop Health & Diseases**\n• Disease diagnosis and treatment\n• Pest identification and control\n• Growth problems and solutions\n• Nutrient deficiencies\n• Plant care and maintenance\n\n🧪 **Fertilizers & Soil**\n• NPK requirements and applications\n• Organic and chemical options\n• Soil testing and improvement\n• Nutrient management\n• Composting and manure use\n\n🐛 **Pest Management**\n• Integrated Pest Management (IPM)\n• Natural and chemical control\n• Beneficial insects\n• Crop-specific pest issues\n• Prevention strategies\n\n🌤️ **Weather & Climate**\n• Temperature stress management\n• Irrigation and water management\n• Drought and flood response\n• Seasonal planning\n• Climate adaptation strategies\n\n📊 **Farming Operations**\n• Crop rotation planning\n• Harvest timing\n• Yield optimization\n• Market price information\n• Government schemes and subsidies\n\n💡 **Quick Tips:**\n• **Ask specific questions** for detailed answers\n• **Describe symptoms** clearly for diagnosis\n• **Include photos** when possible (in future updates)\n• **Monitor regularly** for early problem detection\n• **Keep records** of treatments and results\n\n**What would you like to know about?** Ask me anything about farming!";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Support - AGRIVISION</title>
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
                <a href="ai-support.php" class="active"><i class="fas fa-robot"></i> <?php echo t('ai_support'); ?></a>
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
                        <small><?php echo t('ai_support'); ?></small>
                    </div>
                </div>
                <button class="theme-toggle" title="Toggle Theme">
                    <i class="fas fa-moon"></i>
                </button>
            </div>
            
            <div class="grid-2">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-comments"></i> <?php echo t('ask_question'); ?></h3>
                    </div>
                    
                    <div id="chatContainer" style="height: 400px; overflow-y: auto; padding: 15px; background: var(--bg-light); border-radius: var(--radius); margin-bottom: 20px;">
                        <?php if ($response): ?>
                            <div style="margin-bottom: 15px;">
                                <div style="background: #e3f2fd; padding: 12px 15px; border-radius: var(--radius); display: inline-block; max-width: 80%;">
                                    <strong>You:</strong> <?php echo htmlspecialchars($_POST['query']); ?>
                                </div>
                            </div>
                            <div style="margin-bottom: 15px; text-align: right;">
                                <div style="background: var(--primary-color); color: white; padding: 12px 15px; border-radius: var(--radius); display: inline-block; max-width: 80%; text-align: left;">
                                    <strong>AI:</strong><br>
                                    <?php echo nl2br(htmlspecialchars($response)); ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <div style="text-align: center; padding: 40px; color: var(--text-light);">
                                <i class="fas fa-robot fa-3x" style="margin-bottom: 20px; color: var(--primary-color);"></i>
                                <p><?php echo t('ask_question_message'); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <form method="POST" action="">
                        <div style="display: flex; gap: 10px;">
                            <input type="text" name="query" id="queryInput" required 
                                   placeholder="Type your question here..." 
                                   style="flex: 1; padding: 12px; border: 2px solid #ddd; border-radius: var(--radius);">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i> <?php echo t('send'); ?>
                            </button>
                        </div>
                    </form>
                    
                    <div style="margin-top: 15px;">
                        <p style="font-size: 0.85rem; color: var(--text-light); margin-bottom: 10px;"><strong><?php echo t('quick_questions'); ?>:</strong></p>
                        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                            <button onclick="setQuestion('My crop leaves are turning yellow')" class="btn btn-sm btn-outline">
                                🍂 Yellow leaves?
                            </button>
                            <button onclick="setQuestion('How much fertilizer should I use?')" class="btn btn-sm btn-outline">
                                🧪 Fertilizer amount?
                            </button>
                            <button onclick="setQuestion('How to control aphids?')" class="btn btn-sm btn-outline">
                                🐛 Control aphids?
                            </button>
                            <button onclick="setQuestion('Weather impact on my crops')" class="btn btn-sm btn-outline">
                                🌤️ Weather tips?
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-history"></i> <?php echo t('recent_queries'); ?></h3>
                    </div>
                    <?php if (empty($history)): ?>
                        <p style="text-align: center; color: var(--text-light); padding: 30px;"><?php echo t('no_queries_yet'); ?></p>
                    <?php else: ?>
                        <div style="max-height: 500px; overflow-y: auto;">
                            <?php foreach ($history as $item): ?>
                                <div style="padding: 15px; border-bottom: 1px solid #ddd; margin-bottom: 10px;">
                                    <p style="font-weight: 500; margin-bottom: 5px;">
                                        <i class="fas fa-question-circle" style="color: var(--primary-color);"></i>
                                        <?php echo htmlspecialchars(substr($item['query'], 0, 60)); ?>...
                                    </p>
                                    <p style="font-size: 0.85rem; color: var(--text-light); margin-bottom: 5px;">
                                        <?php echo htmlspecialchars(substr($item['response'], 0, 100)); ?>...
                                    </p>
                                    <small style="color: var(--text-light);">
                                        <i class="fas fa-clock"></i> <?php echo date('M d, H:i', strtotime($item['created_at'])); ?>
                                    </small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        function setQuestion(question) {
            document.getElementById('queryInput').value = question;
            document.getElementById('queryInput').focus();
        }
        
        const chatContainer = document.getElementById('chatContainer');
        if (chatContainer) {
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }
    </script>
    
    <script src="../assets/js/main.js"></script>
</body>
</html>
