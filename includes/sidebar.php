 
<?php
// waiter/includes/sidebar.php
?>
<nav id="sidebar" class="bg-gradient-danger-yellow text-white vh-100 d-flex flex-column p-3 position-fixed">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold fs-5"><i class="fas fa-utensils me-2"></i>HotelPOS</h4>
        <button id="toggleSidebar" class="btn btn-sm text-white">
            <i class="fas fa-bars"></i>
        </button>
    </div>

    <ul class="nav nav-pills flex-column mb-auto">
        <li class="nav-item">
            <a href="../waiter/dashboard.php" class="nav-link text-white">
                <i class="fas fa-home me-2"></i> Dashboard
            </a>
        </li>
        <li>
            <a href="#" class="nav-link text-white">
                <i class="fas fa-receipt me-2"></i> Orders
            </a>
        </li>
        <li>
            <a href="#" class="nav-link text-white">
                <i class="fas fa-print me-2"></i> Print
            </a>
        </li>
        <li>
            <a href="../auth/logout.php" class="nav-link text-white">
                <i class="fas fa-sign-out-alt me-2"></i> Logout
            </a>
        </li>
    </ul>
</nav>

<!-- Add some spacing for the sidebar -->
<div style="margin-left: 240px;"></div>
