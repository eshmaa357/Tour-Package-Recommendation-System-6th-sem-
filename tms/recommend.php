<?php
include('includes/config.php');
include('includes/functions.php');

// Use scoped $pkgid if set, otherwise fall back to $_GET['pkgid']
$pkgid = isset($pkgid) ? intval($pkgid) : (isset($_GET['pkgid']) ? intval($_GET['pkgid']) : 0);
error_log("recommend.php: Processing with pkgid=$pkgid");

if ($pkgid <= 0) {
    error_log("recommend.php: Invalid package ID: $pkgid");
    echo "<p>No valid package ID provided for recommendations.</p>";
    return; // Continue execution instead of exiting
}

// Fetch all packages to build feature vocabulary
$sql = "SELECT PackageId, PackageType, locationType, PackageLocation FROM tbltourpackages";
$query = $dbh->prepare($sql);
$query->execute();
$allPackages = $query->fetchAll(PDO::FETCH_OBJ);

// Build feature vocabulary
$features = [];
foreach ($allPackages as $pkg) {
    if ($pkg->PackageType) {
        $features[] = trim($pkg->PackageType);
    }
    if ($pkg->locationType) {
        $tags = array_map('trim', explode(',', $pkg->locationType));
        foreach ($tags as $tag) {
            if ($tag) $features[] = $tag;
        }
    }
    if ($pkg->PackageLocation) {
        $features[] = trim($pkg->PackageLocation);
    }
}
$features = array_unique(array_filter($features));

// Fetch reference package
$sql = "SELECT PackageId, PackageType, locationType, PackageLocation FROM tbltourpackages WHERE PackageId = :pkgid";
$query = $dbh->prepare($sql);
$query->bindParam(':pkgid', $pkgid, PDO::PARAM_INT);
$query->execute();
$refPackage = $query->fetch(PDO::FETCH_OBJ);
error_log("recommend.php: Reference package query returned " . ($refPackage ? "1" : "0") . " rows for pkgid=$pkgid");

if (!$refPackage) {
    error_log("recommend.php: Reference package not found for PackageId: $pkgid");
    header("Location: /tms/tms/package-list.php?error=reference_package_not_found");
    exit();
}

// Create reference vector
$refVector = array_fill(0, count($features), 0);
if ($refPackage->PackageType) {
    $index = array_search(trim($refPackage->PackageType), $features);
    if ($index !== false) $refVector[$index] = 1;
}
if ($refPackage->locationType) {
    $tags = array_map('trim', explode(',', $refPackage->locationType));
    foreach ($tags as $tag) {
        if ($tag) {
            $index = array_search($tag, $features);
            if ($index !== false) $refVector[$index] = 1;
        }
    }
}
if ($refPackage->PackageLocation) {
    $index = array_search(trim($refPackage->PackageLocation), $features);
    if ($index !== false) $refVector[$index] = 1;
}
if (array_sum($refVector) == 0) {
    error_log("recommend.php: No valid features for reference package: $pkgid");
    echo "<p>No valid features available for recommendations.</p>";
    return;
}

// Calculate cosine similarity for other packages
$sql = "SELECT PackageId, PackageName, PackagePrice, PackageType, locationType, PackageLocation, PackageImage, PackageDuration, MaxSlots, PackageFetures, PackageDetails 
        FROM tbltourpackages WHERE PackageId != :pkgid";
$query = $dbh->prepare($sql);
$query->bindParam(':pkgid', $pkgid, PDO::PARAM_INT);
$query->execute();
$packages = $query->fetchAll(PDO::FETCH_OBJ);

$recommendations = [];

// Define your minimum similarity threshold (0.5 for 50%)
$min_similarity_threshold = 0.5;

foreach ($packages as $package) {
    // Create package vector
    $pkgVector = array_fill(0, count($features), 0);
    if ($package->PackageType) {
        $index = array_search(trim($package->PackageType), $features);
        if ($index !== false) $pkgVector[$index] = 1;
    }
    if ($package->locationType) {
        $tags = array_map('trim', explode(',', $package->locationType));
        foreach ($tags as $tag) {
            if ($tag) {
                $index = array_search($tag, $features);
                if ($index !== false) $pkgVector[$index] = 1;
            }
        }
    }
    if ($package->PackageLocation) {
        $index = array_search(trim($package->PackageLocation), $features);
        if ($index !== false) $pkgVector[$index] = 1;
    }

    // Calculate cosine similarity
    $dotProduct = 0;
    $normA = 0;
    $normB = 0;
    for ($i = 0; $i < count($features); $i++) {
        $dotProduct += $refVector[$i] * $pkgVector[$i];
        $normA += pow($refVector[$i], 2);
        $normB += pow($pkgVector[$i], 2);
    }
    $normA = sqrt($normA);
    $normB = sqrt($normB);
    $similarity = ($normA * $normB) > 0 ? $dotProduct / ($normA * $normB) : 0;

    // Only include packages that meet the minimum similarity threshold
    if ($similarity >= $min_similarity_threshold) {
        $recommendations[] = ['package' => $package, 'similarity' => $similarity];
    }
}

// Sort by similarity (highest to lowest) - this ensures proper ordering across all pages
usort($recommendations, function($a, $b) {
    // First sort by similarity (descending)
    if ($b['similarity'] != $a['similarity']) {
        return $b['similarity'] <=> $a['similarity'];
    }
    // If similarity is the same, sort by package name for consistency
    return strcmp($a['package']->PackageName, $b['package']->PackageName);
});

// Enhanced Pagination settings
$packages_per_page = 5;
$page = isset($_GET['rec_page']) && is_numeric($_GET['rec_page']) && $_GET['rec_page'] > 0 ? (int)$_GET['rec_page'] : 1;
$total_recommendations = count($recommendations);
$total_pages = ceil($total_recommendations / $packages_per_page);

// Ensure page is within valid range
if ($page > $total_pages && $total_pages > 0) {
    $page = $total_pages;
} else if ($total_pages == 0 && $page > 1) { // Handle case where no recommendations are found
    $page = 1;
}


$offset = ($page - 1) * $packages_per_page;

// Slice recommendations for the current page
$paginated_recommendations = array_slice($recommendations, $offset, $packages_per_page);

// Debug information (you can remove this in production)
error_log("recommend.php: Total recommendations: $total_recommendations, Page: $page, Total pages: $total_pages, Offset: $offset");

// Display recommendations
if (!empty($paginated_recommendations)) {
    // Add a header showing the current page and similarity range
    if ($total_pages > 1) {
        $start_index = $offset + 1;
        $end_index = min($offset + $packages_per_page, $total_recommendations);
        echo '<div class="recommendations-header">';
        echo '<p>Showing recommendations ' . $start_index . '-' . $end_index . ' of ' . $total_recommendations . ' (Page ' . $page . ' of ' . $total_pages . ')</p>';
        echo '</div>';
    }
    
    foreach ($paginated_recommendations as $index => $rec) {
        $current_item = $offset + $index + 1;
        ?>
        <div class="rom-btm">
            <div class="col-md-3 room-left wow fadeInLeft animated" data-wow-delay=".3s">
                <img src="/tms/tms/admin/pacakgeimages/<?php echo htmlentities($rec['package']->PackageImage); ?>" class="img-responsive" alt="">
                <small class="similarity-score" style="display: block; text-align: center; margin-top: 5px; color: #666;">
                    Match: <?php echo round($rec['similarity'] * 100, 1); ?>%
                </small>
            </div>
            <div class="col-md-6 room-midle wow fadeInUp animated" data-wow-delay=".3s">
                <h4>Package Name: <?php echo htmlentities($rec['package']->PackageName); ?></h4>
                <h6>Package Type: <?php echo htmlentities($rec['package']->PackageType); ?></h6>
                <p><b>Package Location:</b> <?php echo htmlentities($rec['package']->PackageLocation); ?></p>
                <p><b>Duration:</b> <?php echo htmlentities($rec['package']->PackageDuration); ?> days</p>
                <p><b>Available Slots:</b> <?php echo htmlentities($rec['package']->MaxSlots); ?></p>
                <p><b>Features:</b> <?php echo htmlentities($rec['package']->PackageFetures); ?></p>
            </div>
            <div class="col-md-3 room-right wow fadeInRight animated" data-wow-delay=".3s">
                <h5>Price: NPR <?php echo number_format($rec['package']->PackagePrice, 0); ?></h5>
                <a href="/tms/tms/package-details.php?pid=<?php echo htmlentities($rec['package']->PackageId); ?>&price=<?php echo urlencode($rec['package']->PackagePrice); ?>&destination=<?php echo urlencode($rec['package']->PackageLocation); ?>" class="view">Details</a>
            </div>
            <div class="clearfix"></div>
        </div>
        <?php
    }
} else {
    echo '<p>No packages found matching the recommendation criteria (50% or more similarity).</p>';
}

// Enhanced Pagination Navigation
if ($total_pages > 1) { ?>
    <div class="pagination-wrapper" style="text-align: center; margin: 30px 0;">
        <div class="pagination" style="display: inline-block;">
            
            <?php 
            // Calculate page range to show
            $range = 2; // Number of pages to show on each side of current page
            $start_page = max(1, $page - $range);
            $end_page = min($total_pages, $page + $range);
            ?>
            
            <?php if ($start_page > 1) { ?>
                <a href="?rec_page=1&pkgid=<?php echo urlencode($pkgid); ?>" class="btn btn-default">1</a>
                <?php if ($start_page > 2) { ?>
                    <span class="btn btn-default disabled">...</span>
                <?php } ?>
            <?php } ?>
            
            <?php if ($page > 1) { ?>
                <a href="?rec_page=<?php echo $page - 1; ?>&pkgid=<?php echo urlencode($pkgid); ?>" class="btn btn-default">Previous</a>
            <?php } ?>
            
            <?php for ($i = $start_page; $i <= $end_page; $i++) { ?>
                <a href="?rec_page=<?php echo $i; ?>&pkgid=<?php echo urlencode($pkgid); ?>" 
                   class="btn btn-<?php echo $i == $page ? 'primary' : 'default'; ?>"><?php echo $i; ?></a>
            <?php } ?>
            
            <?php if ($page < $total_pages) { ?>
                <a href="?rec_page=<?php echo $page + 1; ?>&pkgid=<?php echo urlencode($pkgid); ?>" class="btn btn-default">Next</a>
            <?php } ?>
            
            <?php if ($end_page < $total_pages) { ?>
                <?php if ($end_page < $total_pages - 1) { ?>
                    <span class="btn btn-default disabled">...</span>
                <?php } ?>
                <a href="?rec_page=<?php echo $total_pages; ?>&pkgid=<?php echo urlencode($pkgid); ?>" class="btn btn-default"><?php echo $total_pages; ?></a>
            <?php } ?>
            
        </div>
        
        <div style="margin-top: 10px; color: #666;">
            Page <?php echo $page; ?> of <?php echo $total_pages; ?> 
            (<?php echo $total_recommendations; ?> total recommendations)
        </div>
    </div>
<?php } ?>

<style>
.pagination-wrapper .btn {
    margin: 2px;
    padding: 8px 12px;
    text-decoration: none;
    border: 1px solid #ddd;
    color: #333;
}

.pagination-wrapper .btn:hover {
    background-color: #f5f5f5;
}

.pagination-wrapper .btn-primary {
    background-color: #007bff;
    color: white;
    border-color: #007bff;
}

.pagination-wrapper .btn.disabled {
    color: #6c757d;
    pointer-events: none;
    cursor: default;
}

.similarity-score {
    font-size: 12px;
    background-color: #f8f9fa;
    padding: 2px 5px;
    border-radius: 3px;
}

.recommendations-header {
    background-color: #f8f9fa;
    padding: 10px;
    margin-bottom: 20px;
    border-left: 4px solid #007bff;
}
</style>