
<?php

// Calculates the total revenue based on the unit price and quantity.
function calculateRevenue($unitPrice, $quantity) {
    // Multiplies the unit price by the quantity purchased.
    return $unitPrice * $quantity;
}

// Formats a numerical amount into Philippine Peso currency.
function formatCurrency($amount) {
    // Adds the Peso symbol and formats the amount with two decimal places.
    return "₱" . number_format($amount, 2);
}

// Determines the performance status based on actual results and the target.
function getPerformanceBadge($actual, $target) {

    // Checks if the actual performance meets or exceeds the target.
    if ($actual >= $target) {
        return '<span class="badge success">On Target</span>';

    // Checks if the actual performance reaches at least 75% of the target.
    } elseif ($actual >= ($target * 0.75)) {
        return '<span class="badge warning">Moderate</span>';

    // Returns this status if performance is below 75% of the target.
    } else {
        return '<span class="badge danger">Needs Attention</span>';
    }
}

// Generates a visual badge based on the transaction status.
function getStatusBadge($status) {

    // Checks the transaction status and returns the corresponding badge.
    switch ($status) {

        // Displays a green badge for completed transactions.
        case 'Completed':
            return '<span class="badge success">Completed</span>';

        // Displays a yellow badge for transactions being processed.
        case 'Processing':
            return '<span class="badge warning">Processing</span>';

        // Displays a red badge for cancelled transactions.
        case 'Cancelled':
            return '<span class="badge danger">Cancelled</span>';

        // Displays a yellow Pending badge for any other status.
        default:
            return '<span class="badge warning">Pending</span>';
    }
}

// Calculates the Average Order Value (AOV).
function calculateAverageOrderValue($totalRevenue, $totalTransactions) {

    // Prevents division by zero when there are no transactions.
    if ($totalTransactions == 0) {
        return 0;
    }

    // Divides total revenue by the number of transactions.
    return $totalRevenue / $totalTransactions;
}

// Filters transactions based on the selected product category.
function filterTransactionsByCriteria($transactions, $categoryFilter) {

    // Returns all transactions if no category is selected or 'All' is selected.
    if (empty($categoryFilter) || $categoryFilter === 'All') {
        return $transactions;
    }

    // Creates an empty array to store transactions matching the selected category.
    $filtered = [];

    // Loops through each transaction in the original transaction array.
    foreach ($transactions as $txn) {

        // Checks if the transaction category matches the selected category.
        if ($txn['category'] === $categoryFilter) {

            // Adds the matching transaction to the filtered array.
            $filtered[] = $txn;
        }
    }

    // Returns the filtered list of transactions.
    return $filtered;
}

?>
