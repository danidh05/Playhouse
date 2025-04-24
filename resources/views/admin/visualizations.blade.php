@extends('layouts.admin-layout')

@section('title', 'Data Visualizations')

@section('styles')
<style>
    .chart-container {
        position: relative;
        margin: auto;
        height: 350px;
        width: 100%;
    }
</style>
@endsection

@section('content')
<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Data Visualizations</h1>
        <a href="{{ route('admin.dashboard') }}" class="text-blue-600 hover:text-blue-800">
            &larr; Back to Dashboard
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Revenue Trends -->
        <div class="bg-white rounded-lg shadow-md p-4">
            <h2 class="text-lg font-semibold text-gray-700 mb-4">Revenue Trends (Last 30 Days)</h2>
            <div class="chart-container">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>

        <!-- Popular Hours -->
        <div class="bg-white rounded-lg shadow-md p-4">
            <h2 class="text-lg font-semibold text-gray-700 mb-4">Popular Play Session Hours</h2>
            <div class="chart-container">
                <canvas id="hoursChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Expenses by Category -->
    <div class="bg-white rounded-lg shadow-md p-4">
        <h2 class="text-lg font-semibold text-gray-700 mb-4">Monthly Expenses by Item (Last 6 Months)</h2>
        <div class="chart-container">
            <canvas id="expensesChart"></canvas>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Colors for charts
    const colors = [
        'rgba(54, 162, 235, 0.7)',
        'rgba(255, 99, 132, 0.7)',
        'rgba(75, 192, 192, 0.7)',
        'rgba(255, 159, 64, 0.7)',
        'rgba(153, 102, 255, 0.7)',
        'rgba(255, 205, 86, 0.7)',
        'rgba(201, 203, 207, 0.7)',
        'rgba(255, 99, 132, 0.7)',
    ];

    // Revenue Chart
    const revenueCtx = document.getElementById('revenueChart').getContext('2d');
    const revenueChart = new Chart(revenueCtx, {
        type: 'line',
        data: {
            labels: {!! json_encode($labels) !!},
            datasets: [
                {
                    label: 'Total Revenue',
                    data: {!! json_encode($dailyRevenue) !!},
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.1,
                },
                {
                    label: 'Sessions Revenue',
                    data: {!! json_encode($sessionsData) !!},
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 2,
                    fill: false,
                    tension: 0.1
                },
                {
                    label: 'Sales Revenue',
                    data: {!! json_encode($salesData) !!},
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 2,
                    fill: false,
                    tension: 0.1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + value;
                        }
                    }
                }
            },
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': $' + context.raw.toFixed(2);
                        }
                    }
                }
            }
        }
    });

    // Hours Chart
    const hoursCtx = document.getElementById('hoursChart').getContext('2d');
    const hoursChart = new Chart(hoursCtx, {
        type: 'bar',
        data: {
            labels: {!! json_encode($hoursLabels) !!},
            datasets: [{
                label: 'Number of Sessions',
                data: {!! json_encode($hoursCounts) !!},
                backgroundColor: 'rgba(153, 102, 255, 0.7)',
                borderColor: 'rgba(153, 102, 255, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Number of Sessions'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Hour of Day'
                    }
                }
            }
        }
    });

    // Expenses Chart
    const expensesCtx = document.getElementById('expensesChart').getContext('2d');
    
    // Prepare datasets from expense categories
    const expensesDatasets = [];
    const categories = {!! json_encode(array_keys($expensesByCategory)) !!};
    
    categories.forEach((category, index) => {
        expensesDatasets.push({
            label: category,
            data: {!! json_encode($expensesByCategory) !!}[category],
            backgroundColor: colors[index % colors.length],
            borderColor: colors[index % colors.length].replace('0.7', '1'),
            borderWidth: 1
        });
    });

    const expensesChart = new Chart(expensesCtx, {
        type: 'bar',
        data: {
            labels: {!! json_encode($lastSixMonths) !!},
            datasets: expensesDatasets
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    stacked: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + value;
                        }
                    },
                    title: {
                        display: true,
                        text: 'Expenses (USD)'
                    }
                },
                x: {
                    stacked: true,
                    title: {
                        display: true,
                        text: 'Month'
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': $' + context.raw.toFixed(2);
                        }
                    }
                },
                title: {
                    display: true,
                    text: 'Monthly Expenses by Item'
                },
                legend: {
                    position: 'right',
                    labels: {
                        boxWidth: 12
                    }
                }
            }
        }
    });
</script>
@endsection 