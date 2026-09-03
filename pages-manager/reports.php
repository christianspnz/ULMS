<?php
require "../php/auth-logout/auth.php";
requireRole(2);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/output.css">
    <link rel="icon" type="image/png" href="../assets/ulh-logo.png" class="w-24">
    <title>UEH - Reports</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>
    <?php include '../sidebar-manager.php'; ?>
    <main>

        <div class="flex justify-between items-center w-full">
            <span class="page-breadcrumbs">
                Reports
            </span>
            <?php include '../notification-bell.php'; ?>
        </div> 

        <div class="flex justify-between items-center w-full mt-3">
            <div>
                <h2 class="text-3xl font-eurostile-black text-[#234CA1]">Team Progress</h2>
                <p class="text-gray-500 mt-1">Course completion status for your team.</p>
            </div>
        </div>

        <div id="teamTableWrapper" class="bg-white rounded-2xl shadow-md border border-gray-200 p-6 mt-6 overflow-x-auto">
            <p class="text-gray-400 text-center py-10">Loading team progress...</p>
        </div>

    </main>

    <script>
        lucide.createIcons(); 
        async function loadTeamProgress() {

            const wrapper = document.getElementById("teamTableWrapper");

            try {

                const res = await fetch("../php/report/get-team-progress.php");
                const data = await res.json();

                if (data.status !== "success") {
                    wrapper.innerHTML = `<p class="text-red-500 text-center py-10">${data.message}</p>`;
                    return;
                }

                if (data.team.length === 0) {
                    wrapper.innerHTML = `<p class="text-gray-400 text-center py-10">No team members found.</p>`;
                    return;
                }

                if (data.courses.length === 0) {
                    wrapper.innerHTML = `<p class="text-gray-400 text-center py-10">No published courses available for your team's brand yet.</p>`;
                    return;
                }

                let html = `
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="text-left py-3 px-4 font-eurostile-bold text-[#234CA1] sticky left-0 bg-white">Learner</th>
                                ${data.courses.map(c => `
                                    <th class="text-left py-3 px-4 font-eurostile-bold text-[#234CA1] whitespace-nowrap">${escapeHtml(c.course_title)}</th>
                                `).join("")}
                            </tr>
                        </thead>
                        <tbody>
                `;

                data.team.forEach(member => {

                    html += `<tr class="border-b border-gray-100">`;
                    html += `<td class="py-3 px-4 font-medium sticky left-0 bg-white">${escapeHtml(member.first_name)} ${escapeHtml(member.last_name)}</td>`;

                    data.courses.forEach(course => {

                        const entry = data.progress[member.user_id]?.[course.course_id];

                        if (!entry) {
                            html += `<td class="py-3 px-4 text-gray-300 italic text-xs">Not enrolled</td>`;
                        } else {

                            const badgeClass =
                                entry.status === 'Completed' ? 'bg-green-100 text-green-700' :
                                entry.status === 'In Progress' ? 'bg-yellow-100 text-yellow-700' :
                                'bg-gray-100 text-gray-500';

                            html += `
                                <td class="py-3 px-4">
                                    <span class="text-xs font-bold uppercase px-2 py-1 rounded-full ${badgeClass}">${escapeHtml(entry.status)}</span>
                                    <div class="w-24 bg-gray-200 rounded-full h-1.5 mt-1">
                                        <div class="bg-[#234CA1] h-1.5 rounded-full" style="width: ${entry.progress}%"></div>
                                    </div>
                                </td>
                            `;

                        }

                    });

                    html += `</tr>`;

                });

                html += `</tbody></table>`;

                wrapper.innerHTML = html;

            } catch (err) {
                console.error(err);
                wrapper.innerHTML = `<p class="text-red-500 text-center py-10">Failed to load team progress.</p>`;
            }

        }

        function escapeHtml(str) {
            const div = document.createElement("div");
            div.textContent = str ?? "";
            return div.innerHTML;
        }

        loadTeamProgress();

    </script>
</body>
</html>