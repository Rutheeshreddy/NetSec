<?php

declare(strict_types=1);
require_once '../config_session.inc.php' ; // Session configuration
require_once '../db.inc.php'; // Database connection
require_once 'sendMoney_model.inc.php'; // Contains search_users function

function display_money_transfer_form(): void
{
    ?>

    <form method="POST" action="sendMoney_contr.inc.php">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

        <div class="form-container">
            <div class="form-group">
                <label for="username">Recipient:</label>
                <div class="username-container">
                    Search By
                    <select name="search_type" id="search_type">
                        <option value="username">Username</option>
                        <option value="userID">UserID</option>
                    </select>
                    <!-- Username input field -->
                    <input type="text" name="username" id="username" autocomplete="off" required>
                    <div id="suggestions" class="suggestions-box"></div>
                </div>
            </div>

            <div class="form-group">
                <label for="amount">Amount:</label>
                <input type="number" step="0.01" name="amount" id="amount" required>
            </div>

            <div class="form-group">
                <label for="comment">Comment (Optional):</label>
                <textarea name="comment" id="comment"></textarea>
            </div>
        </div>

        <button type="submit" name="transfer">Send Money</button>
    </form>

    <script>
    document.addEventListener("DOMContentLoaded", function () {
        const usernameInput = document.getElementById("username");
        const suggestionsBox = document.getElementById("suggestions");
        const searchTypeDropdown = document.getElementById("search_type");

        suggestionsBox.style.display = "none";

        async function fetchUsers(query, searchType) {
            if (query.length < 2) {
                suggestionsBox.style.display = "none";
                return;
            }

            try {
                const response = await fetch(`searchUsers.php?query=${encodeURIComponent(query)}&type=${searchType}`);

                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }

                const data = await response.json();

                if (!Array.isArray(data) || data.length === 0) {
                    suggestionsBox.style.display = "none";
                    return;
                }

                suggestionsBox.innerHTML = ""; // Clear old suggestions
                data.forEach(user => {
                    let div = document.createElement("div");
                    div.classList.add("suggestion-item");
                    div.setAttribute("tabindex", "0");
                    div.textContent = user; // Display username or userID
                    suggestionsBox.appendChild(div);
                });

                suggestionsBox.style.display = "block";
            } catch (error) {
                console.error("Error fetching users:", error);
                suggestionsBox.style.display = "none"; // Hide suggestions on error
            }
        }

        // Fetch users when input changes
        usernameInput.addEventListener("input", () => {
            const searchType = searchTypeDropdown.value;
            fetchUsers(usernameInput.value, searchType);
        });

        // Fetch users when dropdown changes
        searchTypeDropdown.addEventListener("change", () => {
            const searchType = searchTypeDropdown.value;
            fetchUsers(usernameInput.value, searchType);
        });

        // Handle suggestion selection
        suggestionsBox.addEventListener("click", event => {
            if (event.target.classList.contains("suggestion-item")) {
                usernameInput.value = event.target.textContent;
                suggestionsBox.style.display = "none";
            }
        });

        // Hide suggestions when clicking outside
        document.addEventListener("click", event => {
            if (!suggestionsBox.contains(event.target) && event.target !== usernameInput) {
                suggestionsBox.style.display = "none";
            }
        });

        // Allow keyboard navigation for better UX
        suggestionsBox.addEventListener("keydown", event => {
            if (event.key === "Enter") {
                usernameInput.value = event.target.textContent;
                suggestionsBox.style.display = "none";
                usernameInput.focus();
            }
        });
    });
    </script>

    <?php
    if (!empty($_SESSION["errors_transfer"])) {
        echo '<p class="error-message">' . nl2br(htmlspecialchars($_SESSION["errors_transfer"], ENT_QUOTES, 'UTF-8')) . '</p>';
        unset($_SESSION["errors_transfer"]); // Clear the error after displaying
    }

    if (!empty($_SESSION["transfer_success"])) {
        echo '<p class="success-message">' . nl2br(htmlspecialchars($_SESSION["transfer_success"], ENT_QUOTES, 'UTF-8')) . '</p>';
        unset($_SESSION["transfer_success"]); // Clear success message after displaying
    }
}
?>
 <?php
function display_money_balance()
{
    global $pdo;
    $balance = get_user_balance($pdo,(string)$_SESSION["user_id"]);
    echo '<div class="balance-container">';
    echo '<p>Your Balance: <span class="balance-amount">&#8377;' . number_format((float) $balance, 2) . '</span></p>';
    echo '</div>';
}
?>



