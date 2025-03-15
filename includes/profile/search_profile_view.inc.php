<?php

declare(strict_types=1);
require_once '../config_session.inc.php';
require_once '../db.inc.php';
require_once 'profile_model.inc.php';

function display_profile_search_form(): void
{
    ?>

    <form method="GET" action="pub_profile_view.inc.php" id="searchForm">
        <div class="form-container">
            <div class="form-group">
                <label for="username">Search Profile:</label>
                <div class="username-container">
                    Search By
                    <select name="search_type" id="search_type">
                        <option value="username">Username</option>
                        <option value="userID">UserID</option>
                    </select>
                    <input type="text" name="query" id="username" autocomplete="off" required>
                    <div id="suggestions" class="suggestions-box"></div>
                </div>
            </div>
        </div>

        <button type="submit">Search</button>
    </form>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const usernameInput = document.getElementById("username");
            const suggestionsBox = document.getElementById("suggestions");
            const searchTypeDropdown = document.getElementById("search_type");
            const searchForm = document.getElementById("searchForm");

            suggestionsBox.style.display = "none";

            async function fetchUsers(query, searchType) {
                if (query.length < 2) {
                    suggestionsBox.style.display = "none";
                    return;
                }

                try {
                    console.log("Fetching users:", query, "Search Type:", searchType);
                    const response = await fetch(`search_profile_contr.inc.php?query=${encodeURIComponent(query)}&type=${encodeURIComponent(searchType)}`);

                    if (!response.ok) {
                        throw new Error(`HTTP error! Status: ${response.status}`);
                    }

                    const data = await response.json();
                    console.log("Response Data:", data);

                    if (!Array.isArray(data) || data.length === 0) {
                        suggestionsBox.style.display = "none";
                        return;
                    }

                    suggestionsBox.innerHTML = "";
                    data.forEach(user => {
                        let div = document.createElement("div");
                        div.classList.add("suggestion-item");
                        div.setAttribute("tabindex", "0");

                        if (searchType === "username") {
                            div.textContent = user.username;
                            div.dataset.userId = user.user_id;
                        } else {
                            div.textContent = `User ID: ${user.user_id}`;
                            div.dataset.userId = user.user_id;
                        }

                        suggestionsBox.appendChild(div);
                    });

                    suggestionsBox.style.display = "block";
                } catch (error) {
                    console.error("Error fetching users:", error);
                    suggestionsBox.style.display = "none";
                }
            }

            usernameInput.addEventListener("input", () => {
                fetchUsers(usernameInput.value, searchTypeDropdown.value);
            });

            searchTypeDropdown.addEventListener("change", () => {
                fetchUsers(usernameInput.value, searchTypeDropdown.value);
            });

            suggestionsBox.addEventListener("click", event => {
                if (event.target.classList.contains("suggestion-item")) {
                    window.location.href = `pub_profile_view.inc.php?user_id=${event.target.dataset.userId}`;
                }
            });

            document.addEventListener("click", event => {
                if (!suggestionsBox.contains(event.target) && event.target !== usernameInput) {
                    suggestionsBox.style.display = "none";
                }
            });

            suggestionsBox.addEventListener("keydown", event => {
                if (event.key === "Enter") {
                    window.location.href = `pub_profile_view.inc.php?user_id=${event.target.dataset.userId}`;
                }
            });

            // 🔹 Handle manual form submission correctly
            searchForm.addEventListener("submit", function (event) {
                event.preventDefault(); // Prevent normal form submission

                const searchType = searchTypeDropdown.value;
                const query = usernameInput.value.trim();

                if (!query) {
                    alert("Please enter a search term.");
                    return;
                }

                fetch(`search_profile_contr.inc.php?query=${encodeURIComponent(query)}&type=${encodeURIComponent(searchType)}`)
                    .then(response => response.json())
                    .then(data => {
                        console.log("Form submission data:", data);
                        if (Array.isArray(data) && data.length > 0) {
                            const firstResult = data[0]; // Take the first result
                            window.location.href = `pub_profile_view.inc.php?user_id=${firstResult.user_id}`;
                        } else {
                            alert("No results found.");
                        }
                    })
                    .catch(error => {
                        console.error("Error fetching user:", error);
                        alert("An error occurred while searching.");
                    });
            });

        });
    </script>

    <?php
    if (!empty($_SESSION["errors_search"])) {
        echo '<p class="error-message">' . nl2br(htmlspecialchars($_SESSION["errors_search"], ENT_QUOTES, 'UTF-8')) . '</p>';
        unset($_SESSION["errors_search"]);
    }
}
?>
