<?php

declare(strict_types=1);
require_once '../config_session.inc.php'; 
require_once '../db.inc.php'; 
require_once 'sendMoney_model.inc.php';

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
                    <input type="text" name="username" id="username" autocomplete="off" required maxlength="30">
                    <span id="usernameCount" class="char-counter">30 characters left</span>
                    <div id="suggestions" class="suggestions-box"></div>
                </div>
            </div>

            <div class="form-group">
                <label for="amount">Amount:</label>
                <input type="number" step="0.01" name="amount" id="amount" required>
            </div>

            <div class="form-group">
                <label for="comment">Comment (Optional):</label>
                <textarea name="comment" id="comment" maxlength="300"></textarea>
                <span id="commentCount" class="char-counter">300 characters left</span>
            </div>
        </div>

        <button type="submit" name="transfer">Send Money</button>
    </form>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            function setupCharCounter(inputId, counterId, maxChars) {
                const inputField = document.getElementById(inputId);
                const charCounter = document.getElementById(counterId);

                function updateCounter() {
                    const remaining = maxChars - inputField.value.length;
                    charCounter.textContent = `${remaining} characters left`;
                    charCounter.classList.toggle("warning", remaining <= 10);
                }

                inputField.addEventListener("input", updateCounter);
                updateCounter();
            }

            // **Character counters for username (30) and comment (300)**
            setupCharCounter("username", "usernameCount", 30);
            setupCharCounter("comment", "commentCount", 300);

            const usernameInput = document.getElementById("username");
            const suggestionsBox = document.getElementById("suggestions");
            const searchTypeDropdown = document.getElementById("search_type");

            suggestionsBox.style.display = "none";

            function escapeHTML(str) {
                return str.replace(/[&<>"']/g, function (match) {
                    return {
                        '&': '&amp;',
                        '<': '&lt;',
                        '>': '&gt;',
                        '"': '&quot;',
                        "'": '&#39;'
                    }[match];
                });
            }

            async function fetchUsers(query, searchType) {
                if (query.length < 2) {
                    suggestionsBox.style.display = "none";
                    return;
                }

                const allowedTypes = ["username", "userID"];
                if (!allowedTypes.includes(searchType)) {
                    console.error("Invalid search type");
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

                    suggestionsBox.innerHTML = "";
                    data.forEach(user => {
                        let div = document.createElement("div");
                        div.classList.add("suggestion-item");
                        div.setAttribute("tabindex", "0");
                        div.textContent = escapeHTML(user);
                        suggestionsBox.appendChild(div);
                    });

                    suggestionsBox.style.display = "block";
                } catch (error) {
                    console.error("Error fetching users:", error);
                    suggestionsBox.style.display = "none";
                }
            }

            let debounceTimer;
            usernameInput.addEventListener("input", () => {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    const searchType = searchTypeDropdown.value;
                    fetchUsers(usernameInput.value, searchType);
                }, 300);
            });

            searchTypeDropdown.addEventListener("change", () => {
                fetchUsers(usernameInput.value, searchTypeDropdown.value);
            });

            suggestionsBox.addEventListener("click", event => {
                if (event.target.classList.contains("suggestion-item")) {
                    usernameInput.value = escapeHTML(event.target.textContent);
                    suggestionsBox.style.display = "none";
                }
            });

            document.addEventListener("click", event => {
                if (!suggestionsBox.contains(event.target) && event.target !== usernameInput) {
                    suggestionsBox.style.display = "none";
                }
            });

            suggestionsBox.addEventListener("keydown", event => {
                if (event.key === "Enter") {
                    event.preventDefault();
                    usernameInput.value = escapeHTML(event.target.textContent);
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
    $balance = get_user_balance($pdo, (string)$_SESSION["user_id"]);
    echo '<div class="balance-container">';
    echo '<p>Your Balance: <span class="balance-amount">&#8377;' . number_format((float) $balance, 2) . '</span></p>';
    echo '</div>';
}
?>
