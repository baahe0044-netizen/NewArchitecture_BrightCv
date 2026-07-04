<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/dashboard/dashboard.css">
</head>

<body>

    <!-- OVERLAY -->
    <div id="overlay" class="overlay" onclick="closeSidebar()"></div>

    <!-- SIDEBAR -->
    <div id="sidebar" class="sidebar">
        <h2>🚀 Career Hub</h2>

        <a href="#"><i class="fas fa-home"></i> Dashboard</a>
        <a href="<?= BASE_URL ?>/resume/builder"><i class="fas fa-file-alt"></i> Builder</a>
        <a href="#"><i class="fas fa-chart-line"></i> Analytics</a>
        <a href="#"><i class="fas fa-robot"></i> AI Tools</a>
        <a href="<?= BASE_URL ?>/loginpage.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <!-- MAIN -->
    <div class="main">

        <!-- TOP BAR -->
        <div class="bg-white p-4 shadow flex justify-between items-center">
            <button class="menu-btn" onclick="openSidebar()">
                <i class="fas fa-bars"></i>
            </button>

            <h1 class="font-bold">Career Dashboard</h1>

            <div class="text-sm text-gray-600">Welcome 👋</div>
        </div>

        <!-- STATS -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 p-4">

            <div class="bg-blue-600 text-white p-4 rounded-xl">
                <h2 id="cvCount">0</h2>
                <p>CVs Created</p>
            </div>

            <div class="bg-purple-600 text-white p-4 rounded-xl">
                <h2 id="downloadCount">0</h2>
                <p>Downloads</p>
            </div>

            <div class="bg-green-600 text-white p-4 rounded-xl">
                <h2 id="atsScore">0%</h2>
                <p>ATS Score</p>
            </div>

            <div class="bg-pink-600 text-white p-4 rounded-xl">
                <h2 id="completion">0%</h2>
                <p>Profile Completion</p>
            </div>

        </div>

        <!-- CONTENT -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 p-4">

            <!-- QUICK ACTIONS -->
            <div class="card">
                <h3 class="font-bold mb-3">Quick Actions</h3>

                <button class="w-full bg-blue-600 text-white p-2 rounded mb-2">
                    + Create CV
                </button>

                <button class="w-full bg-green-600 text-white p-2 rounded mb-2">
                    AI Improve CV
                </button>

                <button class="w-full bg-purple-600 text-white p-2 rounded">
                    Run ATS Scan
                </button>
            </div>

            <!-- CV LIST -->
            <div class="card lg:col-span-2">
                <h3 class="font-bold mb-3">Recent CVs</h3>
                <div id="cvsGrid"></div>
            </div>

        </div>

        <!-- AI INSIGHT -->
        <div class="p-4">
            <div class="card">
                <h3 class="font-bold mb-2">AI Career Insight</h3>
                <p id="aiInsight" class="text-gray-600">Loading...</p>
            </div>
        </div>

    </div>

    <script src="<?= BASE_URL ?>/assets/dashboard/dashboard.js"></script>

</body>

</html>