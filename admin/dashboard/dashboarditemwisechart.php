<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$database = "check"; // 🔹 change this

$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// Fetch data
$result = $conn->query("SELECT * FROM item");

// Prepare for chart
$itemnames = [];
$quantities = [];

$total_items = 0;
$total_stock = 0;
$total_value = 0;
$low_stock = 0;

while ($row = $result->fetch_assoc()) {
  $itemnames[] = $row['itemname'];
  $quantities[] = $row['quantity'];
  $total_items++;
  $total_stock += $row['quantity'];
  $total_value += ($row['quantity'] * $row['price']);
  if ($row['quantity'] < 10) { // threshold for low stock
    $low_stock++;
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Item Dashboard</title>

  <!-- Bootstrap & Chart.js -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <style>
    body {
      background-color: #f4f6f9;
      font-family: "Poppins", sans-serif;
    }
    .dashboard-container {
      width: 90%;
      margin: 30px auto;
    }
    .card {
      border: none;
      border-radius: 12px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      transition: 0.3s;
    }
    .card:hover {
      transform: translateY(-3px);
    }
    .card h5 {
      font-size: 16px;
      font-weight: 600;
      color: #555;
    }
    .card h3 {
      font-size: 24px;
      font-weight: 700;
      color: #111;
    }
    .table-container {
      background: #fff;
      border-radius: 12px;
      padding: 25px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      margin-top: 20px;
    }
    .chart-card {
      background: #fff;
      padding: 25px;
      border-radius: 12px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }
  </style>
</head>
<body>

<div class="dashboard-container">
  <h2 class="text-center mb-4">📊 Item Management Dashboard</h2>

  <!-- Dashboard Cards -->
  <div class="row text-center mb-4">
    <div class="col-md-3 mb-3">
      <div class="card p-3">
        <h5>Total Items</h5>
        <h3><?php echo $total_items; ?></h3>
      </div>
    </div>
    <div class="col-md-3 mb-3">
      <div class="card p-3">
        <h5>Total Stock</h5>
        <h3><?php echo $total_stock; ?></h3>
      </div>
    </div>
    <div class="col-md-3 mb-3">
      <div class="card p-3">
        <h5>Total Stock Value (₹)</h5>
        <h3><?php echo number_format($total_value, 2); ?></h3>
      </div>
    </div>
    <div class="col-md-3 mb-3">
      <div class="card p-3">
        <h5>Low Stock Items</h5>
        <h3><?php echo $low_stock; ?></h3>
      </div>
    </div>
  </div>

  <!-- Chart and Table Row -->
  <div class="row">
    <!-- Chart -->
    <div class="col-lg-6 mb-4">
      <div class="chart-card">
        <h5 class="text-center mb-3">Item-wise Quantity Chart</h5>
        <canvas id="itemChart"></canvas>
      </div>
    </div>

    <!-- Table -->
    <div class="col-lg-6 mb-4">
      <div class="table-container">
        <h5 class="text-center mb-3">Item Details</h5>
        <div class="table-responsive">
          <table class="table table-bordered table-hover text-center align-middle">
            <thead class="table-dark">
              <tr>
                <th>ID</th>
                <th>Item Name</th>
                <th>Quantity</th>
                <th>Price (₹)</th>
                <th>Total Value (₹)</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $result2 = $conn->query("SELECT * FROM item");
              if ($result2->num_rows > 0) {
                while ($row = $result2->fetch_assoc()) {
                  $value = $row['quantity'] * $row['price'];
                  echo "<tr>
                          <td>{$row['itemid']}</td>
                          <td>{$row['itemname']}</td>
                          <td>{$row['quantity']}</td>
                          <td>{$row['price']}</td>
                          <td>" . number_format($value, 2) . "</td>
                        </tr>";
                }
              } else {
                echo "<tr><td colspan='5'>No items found</td></tr>";
              }
              ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Chart.js Script -->
<script>
  const ctx = document.getElementById('itemChart').getContext('2d');
  const itemChart = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: <?php echo json_encode($itemnames); ?>,
      datasets: [{
        label: 'Available Quantity',
        data: <?php echo json_encode($quantities); ?>,
        backgroundColor: [
          'rgba(255, 99, 132, 0.6)',
          'rgba(54, 162, 235, 0.6)',
          'rgba(255, 206, 86, 0.6)',
          'rgba(75, 192, 192, 0.6)',
          'rgba(153, 102, 255, 0.6)',
          'rgba(255, 159, 64, 0.6)'
        ],
        borderColor: 'rgba(0,0,0,0.1)',
        borderWidth: 1,
        borderRadius: 6
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { position: 'top' },
        title: {
          display: true,
          text: 'Quantity of Each Item'
        }
      },
      scales: {
        y: { beginAtZero: true, title: { display: true, text: 'Quantity' } },
        x: { title: { display: true, text: 'Item Name' } }
      }
    }
  });
</script>

</body>
</html>
