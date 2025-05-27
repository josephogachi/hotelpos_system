 
<?php
// waiter/includes/footer.php
?>
<!-- Bootstrap Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- jQuery (Optional if you want to use jQuery for interactions) -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>

<!-- Sidebar Toggle Script -->
<script>
    document.getElementById("toggleSidebar").addEventListener("click", function () {
        document.getElementById("sidebar").classList.toggle("collapsed");
        document.body.classList.toggle("sidebar-collapsed");
    });
</script>

<!-- Custom JS -->
<script src="../assets/js/waiter.js"></script>
</body>
</html>
