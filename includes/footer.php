<?php if(basename($_SERVER['PHP_SELF']) !== 'login.php'): ?>
        </div> <!-- /.container-fluid -->
    </div> <!-- /.content -->
</div> <!-- /.d-flex -->
<?php endif; ?>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Common JS Functions -->
<script src="assets/js/common.js"></script>

<?php if(isset($extra_js)): ?>
    <?php echo $extra_js; ?>
<?php endif; ?>

<?php if (isset($_GET['success'])): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        showAlert('<?php echo htmlspecialchars($_GET['success']); ?>', 'success');
    });
</script>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        showAlert('<?php echo htmlspecialchars($_GET['error']); ?>', 'danger');
    });
</script>
<?php endif; ?>

</body>
</html>
