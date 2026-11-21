<?php

function calculateRevenue($unitPrice, $quantity) {
    return $unitPrice * $quantity;
}

function formatCurrency($amount) {
    return "₱" . number_format($amount, 2);
}

function getPerformanceBadge($actual, $target) {
    if ($actual >= $target) {
        return '<span class="badge success">On Target</span>';
    } elseif ($actual >= ($target * 0.75)) {
        return '<span class="badge warning">Moderate</span>';
    } else {
        return '<span class="badge danger">Needs Attention</span>';
    }
}

function getStatusBadge($status) {
    switch ($status) {
        case 'Completed':
            return '<span class="badge success">Completed</span>';
        case 'Processing':
            return '<span class="badge warning">Processing</span>';
        case 'Cancelled':
            return '<span class="badge danger">Cancelled</span>';
        default:
            return '<span class="badge warning">Pending</span>';
    }
}

function calculateAverageOrderValue($totalRevenue, $totalTransactions) {
    if ($totalTransactions == 0) {
        return 0;
    }
    return $totalRevenue / $totalTransactions;
}

function filterTransactionsByCriteria($transactions, $categoryFilter) {
    if (empty($categoryFilter) || $categoryFilter === 'All') {
        return $transactions;
    }
    
    $filtered = [];
    foreach ($transactions as $txn) {
        if ($txn['category'] === $categoryFilter) {
            $filtered[] = $txn;
        }
    }
    return $filtered;
}
?>