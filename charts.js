// Additional chart functionality for advanced analytics
class AdvancedCharts {
  constructor() {
    this.charts = {};
  }

  // Initialize all charts
  initCharts() {
    this.initEfficiencyChart();
    this.initMonthlyComparisonChart();
    this.initExpenseBreakdownChart();
  }

  // Efficiency chart (Rp per hour)
  initEfficiencyChart() {
    const ctx = document.getElementById("efficiencyChart");
    if (!ctx) return;

    // Sample data - in real app, this would come from API
    const data = {
      labels: ["Sen", "Sel", "Rab", "Kam", "Jum", "Sab", "Min"],
      datasets: [
        {
          label: "Rp per Jam",
          data: [25000, 28000, 22000, 30000, 27000, 32000, 29000],
          backgroundColor: "rgba(59, 130, 246, 0.5)",
          borderColor: "#3B82F6",
          borderWidth: 2,
        },
      ],
    };

    this.charts.efficiency = new Chart(ctx, {
      type: "bar",
      data: data,
      options: {
        responsive: true,
        scales: {
          y: {
            beginAtZero: true,
            title: {
              display: true,
              text: "Rupiah per Jam",
            },
          },
        },
      },
    });
  }

  // Monthly comparison chart
  initMonthlyComparisonChart() {
    const ctx = document.getElementById("monthlyChart");
    if (!ctx) return;

    const data = {
      labels: ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun"],
      datasets: [
        {
          label: "Pendapatan",
          data: [4500000, 5200000, 4800000, 5500000, 6000000, 5800000],
          backgroundColor: "rgba(16, 185, 129, 0.5)",
          borderColor: "#10B981",
          borderWidth: 2,
        },
        {
          label: "Pengeluaran",
          data: [1200000, 1500000, 1300000, 1400000, 1600000, 1500000],
          backgroundColor: "rgba(239, 68, 68, 0.5)",
          borderColor: "#EF4444",
          borderWidth: 2,
        },
      ],
    };

    this.charts.monthly = new Chart(ctx, {
      type: "bar",
      data: data,
      options: {
        responsive: true,
        scales: {
          y: {
            beginAtZero: true,
          },
        },
      },
    });
  }

  // Expense breakdown chart
  initExpenseBreakdownChart() {
    const ctx = document.getElementById("expenseChart");
    if (!ctx) return;

    const data = {
      labels: ["Bensin", "Makan", "Servis", "Tagihan", "Lainnya"],
      datasets: [
        {
          data: [40, 25, 15, 10, 10],
          backgroundColor: [
            "#3B82F6",
            "#EF4444",
            "#10B981",
            "#F59E0B",
            "#8B5CF6",
          ],
        },
      ],
    };

    this.charts.expense = new Chart(ctx, {
      type: "doughnut",
      data: data,
      options: {
        responsive: true,
        plugins: {
          legend: {
            position: "bottom",
          },
        },
      },
    });
  }
}

// Initialize charts when DOM is loaded
document.addEventListener("DOMContentLoaded", function () {
  const advancedCharts = new AdvancedCharts();
  advancedCharts.initCharts();
});
