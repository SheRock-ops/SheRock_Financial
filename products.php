
<?php

session_start();

require_once "includes/db.php";

/* ========================================
   Helper Functions
======================================== */

function product_escape($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

function product_money($amount) {
    return "$" . number_format((float) $amount, 2);
}

/* ========================================
   Load Active Investment Products
======================================== */

$products = [];
$error = "";

try {

    $stmt = $conn->prepare(
        "SELECT product_id, product_name, product_type,
                description, interest_rate, term_months,
                minimum_deposit, payout_frequency
         FROM products
         WHERE is_active = 1
         ORDER BY interest_rate DESC, product_name ASC"
    );

    $stmt->execute();

    $result = $stmt->get_result();

    $products = $result->fetch_all(MYSQLI_ASSOC);

    $stmt->close();

} catch (mysqli_sql_exception $exception) {

    error_log($exception->getMessage());

    $error = "Investment products are temporarily unavailable.";

}

/* ========================================
   Product Categories
======================================== */

$categories = [];

foreach ($products as $product) {

    $type = $product["product_type"];

    if (!in_array($type, $categories, true)) {
        $categories[] = $type;
    }

}

sort($categories);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Explore and compare GICs and investment products with SheRock Financial.">

    <title>Investment Products | SheRock Financial</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="css/style.css?v=2">
</head>
<body>

    <!-- Header -->
    <header>

        <div class="logo">
            <h1>SheRock <span>Financial</span></h1>
        </div>

        <nav aria-label="Main navigation">

            <a href="index.php">Home</a>

            <?php if (isset($_SESSION["user_id"])): ?>

                <a href="dashboard.php">Dashboard</a>
                <a href="savings_goals.php">Savings Goals</a>
                <a href="products.php" aria-current="page">Products</a>
                <a href="logout.php" class="btn">Logout</a>

            <?php else: ?>

                <a href="about.php">About</a>
                <a href="products.php" aria-current="page">Products</a>
                <a href="login.php" class="btn">Login</a>

            <?php endif; ?>

        </nav>

    </header>

    <!-- Main Content -->
    <main>

        <!-- Investment Products Hero -->
        <section class="products-hero">

            <p class="section-label">
                INVEST IN YOUR FINANCIAL FUTURE
            </p>

            <h2>Explore Investment Products</h2>

            <p>
                Discover savings and investment opportunities
                designed to help you plan confidently and
                grow your financial future.
            </p>

        </section>

        <!-- Investment Products Section -->
        <section class="products-section">

            <div class="products-container">

                <p class="section-label">
                    SAVE SMARTER. GROW STRONGER.
                </p>

                <h2>Available Investment Products</h2>

                <p class="products-intro">
                    Compare interest rates, investment terms,
                    and minimum deposits to find an option
                    that suits your financial goals.
                </p>

                <?php if ($error !== ""): ?>

                    <p class="auth-error" role="alert">
                        <?php echo product_escape($error); ?>
                    </p>

                <?php elseif (empty($products)): ?>

                    <!-- Empty Products Message -->
                    <div class="products-empty">

                        <h3>No Investment Products Available</h3>

                        <p>
                            New investment products will appear
                            here when they become available.
                        </p>

                    </div>

                <?php else: ?>

                    <!-- Product Filters -->
                    <div class="products-filters">

                        <label for="productFilter">
                            Product Category
                        </label>

                        <select id="productFilter">

                            <option value="all">All Products</option>

                            <?php foreach ($categories as $category): ?>

                                <option value="<?php echo product_escape($category); ?>">
                                    <?php echo product_escape($category); ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                        <label for="productSort">
                            Sort Products
                        </label>

                        <select id="productSort">

                            <option value="rate-high">
                                Highest Interest Rate
                            </option>

                            <option value="rate-low">
                                Lowest Interest Rate
                            </option>

                            <option value="term-short">
                                Shortest Investment Term
                            </option>

                            <option value="deposit-low">
                                Lowest Minimum Deposit
                            </option>

                        </select>

                    </div>

                    <!-- Investment Product Cards -->
                    <div class="products-grid" id="productsGrid">

                        <?php foreach ($products as $product): ?>

                            <article class="product-card"
                                     data-type="<?php echo product_escape($product["product_type"]); ?>"
                                     data-rate="<?php echo (float) $product["interest_rate"]; ?>"
                                     data-term="<?php echo $product["term_months"] !== null ? (int) $product["term_months"] : -1; ?>"
                                     data-deposit="<?php echo (float) $product["minimum_deposit"]; ?>">

                                <p class="section-label">
                                    <?php echo product_escape($product["product_type"]); ?>
                                </p>

                                <h3>
                                    <?php echo product_escape($product["product_name"]); ?>
                                </h3>

                                <!-- Interest Rate -->
                                <div class="product-rate">

                                    <strong>
                                        <?php echo number_format((float) $product["interest_rate"], 2); ?>%
                                    </strong>

                                    <span>Annual Interest Rate</span>

                                </div>

                                <!-- Product Description -->
                                <p class="product-description">
                                    <?php echo product_escape(
                                        $product["description"] ?: "Explore this investment opportunity."
                                    ); ?>
                                </p>

                                <!-- Product Details -->
                                <div class="product-details">

                                    <p>
                                        <span>Investment Term</span>

                                        <strong>
                                            <?php
                                            echo $product["term_months"] !== null
                                                ? (int) $product["term_months"] . " months"
                                                : "Flexible";
                                            ?>
                                        </strong>
                                    </p>

                                    <p>
                                        <span>Minimum Deposit</span>

                                        <strong>
                                            <?php echo product_money($product["minimum_deposit"]); ?>
                                        </strong>
                                    </p>

                                    <p>
                                        <span>Interest Payout</span>

                                        <strong>
                                            <?php echo product_escape(
                                                $product["payout_frequency"] ?: "At maturity"
                                            ); ?>
                                        </strong>
                                    </p>

                                </div>

                                <!-- Compare Product -->
                                <label class="product-compare">

                                    <input type="checkbox"
                                           class="compare-checkbox"
                                           value="<?php echo (int) $product["product_id"]; ?>">

                                    Compare This Product

                                </label>

                            </article>

                        <?php endforeach; ?>

                    </div>

                    <!-- No Matching Products -->
                    <div class="products-empty"
                         id="productsNoMatch"
                         role="status"
                         hidden>

                        <p>
                            No products match the selected category.
                        </p>

                    </div>

                    <!-- Investment Product Comparison -->
                    <div class="products-comparison">

                        <p class="section-label">
                            MAKE INFORMED DECISIONS
                        </p>

                        <h2>Compare Investment Products</h2>

                        <p>
                            Select up to three investment products
                            to compare interest rates, investment
                            terms, and minimum deposits.
                        </p>

                        <div class="products-table-wrapper">

                            <table id="comparisonTable">

                                <thead>
                                    <tr>
                                        <th scope="col">Product</th>
                                        <th scope="col">Interest Rate</th>
                                        <th scope="col">Term</th>
                                        <th scope="col">Minimum Deposit</th>
                                    </tr>
                                </thead>

                                <tbody id="comparisonBody">

                                    <tr>
                                        <td colspan="4">
                                            Select products above to compare.
                                        </td>
                                    </tr>

                                </tbody>

                            </table>

                        </div>

                    </div>

                <?php endif; ?>

            </div>

        </section>

    </main>

    <!-- Footer -->
    <footer>

        <p>&copy; 2026 SheRock Financial. All rights reserved.</p>

        <p>Save Smarter. Grow Stronger.</p>

        <p class="disclaimer">
            For educational and financial planning purposes only.
            Rates shown are illustrative unless independently verified.
            Not financial or investment advice.
        </p>

    </footer>

    <!-- Product Filtering and Comparison -->
    <script>

        /* ========================================
           Product Filtering and Sorting
        ======================================== */

        const productFilter = document.getElementById("productFilter");
        const productSort = document.getElementById("productSort");
        const productsGrid = document.getElementById("productsGrid");

        if (productFilter && productSort && productsGrid) {

            const cards = Array.from(
                productsGrid.querySelectorAll(".product-card")
            );

            const noMatch = document.getElementById("productsNoMatch");

            function updateProducts() {

                const selectedType = productFilter.value;
                const sortValue = productSort.value;

                let visibleCount = 0;

                cards.forEach(function(card) {

                    const matches = selectedType === "all" ||
                        card.dataset.type === selectedType;

                    card.hidden = !matches;

                    if (matches) {
                        visibleCount++;
                    }

                });

                cards.sort(function(a, b) {

                    if (sortValue === "rate-low") {

                        return Number(a.dataset.rate) -
                               Number(b.dataset.rate);

                    }

                    if (sortValue === "term-short") {

                        const termA = Number(a.dataset.term);
                        const termB = Number(b.dataset.term);

                        if (termA < 0 && termB < 0) {
                            return 0;
                        }

                        if (termA < 0) {
                            return 1;
                        }

                        if (termB < 0) {
                            return -1;
                        }

                        return termA - termB;

                    }

                    if (sortValue === "deposit-low") {

                        return Number(a.dataset.deposit) -
                               Number(b.dataset.deposit);

                    }

                    return Number(b.dataset.rate) -
                           Number(a.dataset.rate);

                });

                cards.forEach(function(card) {
                    productsGrid.appendChild(card);
                });

                noMatch.hidden = visibleCount > 0;

            }

            productFilter.addEventListener("change", updateProducts);
            productSort.addEventListener("change", updateProducts);

            /* ========================================
               Product Comparison
            ======================================== */

            const checkboxes = document.querySelectorAll(".compare-checkbox");
            const comparisonBody = document.getElementById("comparisonBody");

            function updateComparison() {

                const selected = Array.from(checkboxes).filter(
                    function(item) {
                        return item.checked;
                    }
                );

                comparisonBody.innerHTML = "";

                if (selected.length === 0) {

                    const row = comparisonBody.insertRow();
                    const cell = row.insertCell();

                    cell.colSpan = 4;
                    cell.textContent = "Select products above to compare.";

                    return;

                }

                selected.forEach(function(item) {

                    const card = item.closest(".product-card");
                    const row = comparisonBody.insertRow();

                    const name = card.querySelector("h3").textContent.trim();

                    const rate = Number(card.dataset.rate).toFixed(2) + "%";

                    const term = Number(card.dataset.term) >= 0
                        ? card.dataset.term + " months"
                        : "Flexible";

                    const deposit = Number(card.dataset.deposit)
                        .toLocaleString("en-CA", {
                            style: "currency",
                            currency: "CAD"
                        });

                    [name, rate, term, deposit].forEach(function(value) {

                        const cell = row.insertCell();

                        cell.textContent = value;

                    });

                });

            }

            checkboxes.forEach(function(checkbox) {

                checkbox.addEventListener("change", function() {

                    const selectedCount = Array.from(checkboxes).filter(
                        function(item) {
                            return item.checked;
                        }
                    ).length;

                    if (selectedCount > 3) {

                        checkbox.checked = false;

                        alert("You can compare up to three products.");

                        return;

                    }

                    updateComparison();

                });

            });

            updateProducts();

        }

    </script>

</body>
</html>
