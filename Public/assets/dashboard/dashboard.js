const API = "/LunettiStar/api/dashboard.php";
        let cvs = [];

        /* =========================
           SIDEBAR CONTROLS
        ========================= */
        function openSidebar() {
            document.getElementById("sidebar").classList.add("open");
            document.getElementById("overlay").classList.add("active");
        }

        function closeSidebar() {
            document.getElementById("sidebar").classList.remove("open");
            document.getElementById("overlay").classList.remove("active");
        }

        /* =========================
           INIT
        ========================= */
        window.addEventListener("DOMContentLoaded", () => {
            loadStats();
            loadCVs();
            loadAIInsight();
        });

        /* =========================
           STATS
        ========================= */
        async function loadStats() {
            const res = await fetch(API + "?action=dashboard_data");
            const data = await res.json();

            if (data.success) {
                document.getElementById("cvCount").innerText = data.data.total_cvs;
                document.getElementById("downloadCount").innerText = data.data.total_downloads;

                document.getElementById("atsScore").innerText = data.data.ats_score;
                document.getElementById("completion").innerText = data.data.completion;
            }
        }

        /* =========================
           CVS
        ========================= */
        async function loadCVs() {
            const res = await fetch(API + "?action=get_resumes");
            const data = await res.json();

            if (data.success) {
                cvs = data.data;
                renderCVs();
            }
        }

        function renderCVs() {
            const box = document.getElementById("cvsGrid");

            if (!cvs.length) {
                box.innerHTML = "<p>No CVs yet. Create one.</p>";
                return;
            }

            box.innerHTML = cvs.map(cv => `
        <div class="border p-3 rounded mb-2">
            <h4 class="font-bold">${cv.name}</h4>

            <a href="index.php?page=resume/builder&resume_id=${cv.id}"
               class="text-blue-600 text-sm">
               Continue Editing →
            </a>
        </div>
    `).join("");
        }

        /* =========================
           AI INSIGHT
        ========================= */
        function loadAIInsight() {
            const insights = [
                "Improve your experience section with measurable achievements.",
                "Add more technical skills to improve ATS score.",
                "Your CV is strong — consider adding certifications.",
                "Optimize your summary for better job matching."
            ];

            document.getElementById("aiInsight")
                .innerText = insights[Math.floor(Math.random() * insights.length)];
        }