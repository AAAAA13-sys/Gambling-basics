    <?php
    session_start();

    // Keep pattern labels & base points in sync with api.php
    $patterns = [
        'odd' => ['label' => 'Odd', 'points' => 10],
        'even' => ['label' => 'Even', 'points' => 10],
        'low' => ['label' => 'Low (2–6)', 'points' => 10],
        'high' => ['label' => 'High (7–12)', 'points' => 10],
    ];

    $score = isset($_SESSION['score']) ? (int)$_SESSION['score'] : 100;
    $history = isset($_SESSION['history']) ? $_SESSION['history'] : [];

    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Dice Betting Game</title>
        <link rel="stylesheet" href="style.css" />
    </head>
    <body>
        <div class="fake-ad fake-ad-left" aria-hidden="true">
            <div class="ad-pill">ADVERTISEMENT</div>
            <div class="ad-content">
                <div class="ad-title">Get 200% bonus</div>
                <div class="ad-sub">Use code <strong>ROLLFAST</strong> at checkout</div>
                <div class="ad-cta">Claim now</div>
            </div>
        </div>
        <div class="fake-ad fake-ad-right" aria-hidden="true">
            <div class="ad-pill">SPONSORED</div>
            <div class="ad-content">
                <div class="ad-title">VIP Boost</div>
                <div class="ad-sub">Join the club for extra spins</div>
                <div class="ad-cta">Join now</div>
            </div>
        </div>
        <main class="container">
        <header class="header">
            <h1>Live dice table</h1>
            <p class="tagline">Place your bet and roll the dice</p>
        </header>

        <section class="game">
            <div class="layout">
                <div class="main-panel">
                    <div class="panel-header">
                        <div class="panel-title">Dice wheel</div>
                        <div class="panel-score">Score: <span id="displayScore"><?= $score ?></span></div>
                    </div>

                    <div class="panel-body">
                        <div class="dice-container" aria-label="Dice roll" role="img">
                            <img id="die1" class="die" src="img/dice-six-faces-one.svg" alt="Die face 1" aria-hidden="true" />
                            <img id="die2" class="die" src="img/dice-six-faces-one.svg" alt="Die face 1" aria-hidden="true" />
                        </div>

                        <div class="result-panel">
                            <div class="result-row">
                                <div class="result-label">Your bet:</div>
                                <div id="displayBet" class="result-value">—</div>
                            </div>
                            <div class="result-row">
                                <div class="result-label">Roll total:</div>
                                <div id="displayNumber" class="result-value">—</div>
                            </div>
                            <div class="result-row">
                                <div class="result-label">Result:</div>
                                <div id="displayResult" class="result-value result">—</div>
                            </div>
                        </div>
                    </div>
                </div>

                <aside class="sidebar">
                    <div class="panel-header">
                        <div class="panel-title">Roll history</div>
                        <div class="panel-meta">online</div>
                    </div>

                    <div class="panel-body history-panel">
                        <ul id="historyList" class="history-list"></ul>
                    </div>

                    <div class="panel-footer">
                        <div class="bet-grid" role="radiogroup" aria-label="Betting patterns">
                            <label class="bet-option">
                                <input type="radio" name="betPattern" value="odd" checked />
                                <span>Odd</span>
                            </label>
                            <label class="bet-option">
                                <input type="radio" name="betPattern" value="even" />
                                <span>Even</span>
                            </label>
                            <label class="bet-option">
                                <input type="radio" name="betPattern" value="low" />
                                <span>Low 2–6</span>
                            </label>
                            <label class="bet-option">
                                <input type="radio" name="betPattern" value="high" />
                                <span>High 7–12</span>
                            </label>
                        </div>

                        <div class="bet-amounts" aria-label="Select bet amount">
                            <div class="bet-amount-label">Stake</div>
                            <div class="bet-buttons">
                                <button type="button" class="bet-amount" data-bet="0.25">1/4</button>
                                <button type="button" class="bet-amount" data-bet="0.5">1/2</button>
                                <button type="button" class="bet-amount" data-bet="1">All in</button>
                            </div>
                            <input id="betStake" type="number" min="1" value="20" placeholder="Stake" style="width:100%; padding:10px; border-radius:12px; border:1px solid rgba(255,255,255,0.18); background:rgba(0,0,0,0.35); color:inherit; margin-top:10px;" />
                        </div>

                        <div class="button-bar">
                            <button id="placeBet" class="primary">Place bet</button>
                            <button id="playAgain" class="secondary" style="display:none;">Play again</button>
                            <button id="resetScore" class="danger">Reset</button>
                        </div>
                    </div>
                </aside>
            </div>
        </section>
        </main>

        <div id="confirmModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="confirmTitle">
        <div class="modal-panel">
            <h2 id="confirmTitle">Confirm your bet</h2>
            <p id="confirmBody">...</p>
            <div class="modal-buttons">
            <button id="confirmCancel" class="secondary">Cancel</button>
            <button id="confirmYes" class="primary">Confirm</button>
            </div>
        </div>
        </div>

        <script>
        const patterns = <?= json_encode(array_map(fn($p) => ['label' => $p['label'], 'points' => $p['points']], $patterns), JSON_UNESCAPED_UNICODE) ?>;
        let score = <?= json_encode($score) ?>;
        let history = <?= json_encode($history, JSON_UNESCAPED_UNICODE) ?>;
        let liveBet = null;

        const placeBetEl = document.getElementById('placeBet');
        const playAgainEl = document.getElementById('playAgain');
        const resetScoreEl = document.getElementById('resetScore');
        const displayBetEl = document.getElementById('displayBet');
        const displayNumberEl = document.getElementById('displayNumber');
        const displayResultEl = document.getElementById('displayResult');
        const displayScoreEl = document.getElementById('displayScore');
        const historyListEl = document.getElementById('historyList');
        const diceContainer = document.querySelector('.dice-container');
        const die1El = document.getElementById('die1');
        const die2El = document.getElementById('die2');

        const betOptionEls = document.querySelectorAll('input[name="betPattern"]');
        const betAmountButtons = document.querySelectorAll('.bet-amount');
        const betStakeEl = document.getElementById('betStake');

        let activeBetAmount = Number(betStakeEl.value) || 0;
        let activeBetType = null; // '0.25', '0.5', '1' for 1/4, 1/2, all-in

        const diceFaceMap = {
            1: 'img/dice-six-faces-one.svg',
            2: 'img/dice-six-faces-two.svg',
            3: 'img/dice-six-faces-three.svg',
            4: 'img/dice-six-faces-four.svg',
            5: 'img/dice-six-faces-five.svg',
            6: 'img/dice-six-faces-six.svg',
        };

        function setDieFace(el, value) {
            const face = Math.min(6, Math.max(1, Number(value) || 1));
            el.src = diceFaceMap[face];
            el.alt = `Die showing ${face}`;
        }

        function getSelectedPattern() {
            const selected = document.querySelector('input[name="betPattern"]:checked');
            return selected ? selected.value : 'odd';
        }

        function updateBetStakeInput(amount) {
            betStakeEl.value = amount;
            activeBetAmount = amount;
        }

        function computeStakeFromType(type) {
            const currentScore = Number(score) || 0;
            if (!currentScore) return 1;
            const fraction = Number(type);
            if (Number.isFinite(fraction) && fraction > 0) {
                if (fraction >= 1) {
                    return Math.max(1, currentScore);
                }
                return Math.max(1, Math.floor(currentScore * fraction));
            }
            return Math.max(1, currentScore);
        }

        function setActiveBetType(type) {
            activeBetType = type;
            betAmountButtons.forEach((btn) => {
            btn.classList.toggle('active', btn.dataset.bet === type);
            });
            if (type) {
            updateBetStakeInput(computeStakeFromType(type));
            }
        }

        function getBetAmount() {
            const parsed = Number(betStakeEl.value);
            if (Number.isNaN(parsed) || parsed <= 0) return 0;
            return parsed;
        }

        function rollDiceAnimation() {
            diceContainer.classList.add('rolling');
            return () => diceContainer.classList.remove('rolling');
        }

        function applyRoll(dice) {
            if (!Array.isArray(dice) || dice.length < 2) return;
            setDieFace(die1El, dice[0]);
            setDieFace(die2El, dice[1]);
        }

        // Initialize dice faces and bet UI
        applyRoll([1, 1]);
        displayBetEl.textContent = `${formatPatternLabel(getSelectedPattern())} ($${getBetAmount()})`;

        const confirmModal = document.getElementById('confirmModal');
        const confirmBody = document.getElementById('confirmBody');
        const confirmCancel = document.getElementById('confirmCancel');
        const confirmYes = document.getElementById('confirmYes');

        function formatPatternLabel(key) {
            return patterns[key]?.label ?? key;
        }

        function renderHistory() {
            historyListEl.innerHTML = '';

            if (liveBet) {
            const liveItem = document.createElement('li');
            liveItem.className = 'history-item live';
            liveItem.innerHTML = `
                <span><strong>Live bet</strong> ${liveBet.pattern} ×${liveBet.bet}</span>
                <span style="color: rgba(255,255,255,0.8);">Rolling…</span>
            `;
            historyListEl.appendChild(liveItem);
            }

            if (!history?.length && !liveBet) {
            const empty = document.createElement('li');
            empty.className = 'history-item';
            empty.innerHTML = '<span>No rounds yet</span>';
            historyListEl.appendChild(empty);
            return;
            }

            history.slice(0, 10).forEach((round) => {
            const item = document.createElement('li');
            item.className = 'history-item';
            item.innerHTML = `
                <span><strong>${round.pattern}</strong> ×${round.bet}</span>
                <span>${round.win ? 'WIN' : 'LOSE'} (+${round.points})</span>
                <span style="grid-column: 1 / -1; color: rgba(255,255,255,0.6); font-size: 0.85rem;">Roll: ${round.dice?.[0]} + ${round.dice?.[1]} = ${round.generated}</span>
            `;
            historyListEl.appendChild(item);
            });
        }

        function updateScoreAndHistory(data) {
            if (typeof data.score === 'number') {
            score = data.score;
            displayScoreEl.textContent = score;
            }
            if (Array.isArray(data.history)) {
            history = data.history;
            renderHistory();
            }
        }

        function showResult(pattern, generated, win, points) {
            displayNumberEl.textContent = generated;
            displayResultEl.textContent = win ? `You Win! (+${points})` : 'You Lose';
            displayResultEl.classList.toggle('win', win);
            displayResultEl.classList.toggle('lose', !win);
        }

        function setLoading(isLoading) {
            placeBetEl.disabled = isLoading;
            resetScoreEl.disabled = isLoading;
            betOptionEls.forEach((el) => (el.disabled = isLoading));
            betAmountButtons.forEach((btn) => (btn.disabled = isLoading));
            placeBetEl.textContent = isLoading ? 'Rolling…' : 'Place Bet';
        }

        async function callApi(payload) {
            setLoading(true);
            try {
            const res = await fetch('api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
            });
            return await res.json();
            } catch (err) {
            return { success: false, message: 'Network error' };
            } finally {
            setLoading(false);
            }
        }

        function showConfirm(patternLabel, betAmount) {
            confirmBody.textContent = `Place a ${betAmount}‑credit bet on “${patternLabel}”?`;
            confirmModal.classList.add('open');
        }

        function hideConfirm() {
            confirmModal.classList.remove('open');
        }

        betAmountButtons.forEach((button) => {
            button.addEventListener('click', () => {
            setActiveBetType(button.dataset.bet);
            });
        });

        placeBetEl.addEventListener('click', () => {
            const selected = getSelectedPattern();
            const betAmount = getBetAmount();
            if (betAmount <= 0) {
                alert('Please enter a valid stake amount.');
                return;
            }
            if (betAmount > score) {
                alert('Stake cannot exceed your current score.');
                return;
            }
            showConfirm(formatPatternLabel(selected), betAmount);
        });

        confirmCancel.addEventListener('click', () => {
            hideConfirm();
        });

        confirmYes.addEventListener('click', async () => {
            hideConfirm();
            const selected = getSelectedPattern();
            const betAmount = getBetAmount();

            displayBetEl.textContent = `${formatPatternLabel(selected)} ($${betAmount})`;
            liveBet = { pattern: formatPatternLabel(selected), bet: betAmount };
            renderHistory();

            // Roll dice animation while waiting for API response
            const stopRolling = rollDiceAnimation();
            displayNumberEl.textContent = 'Rolling…';

            const result = await callApi({ action: 'play', pattern: selected, bet: betAmount });
            stopRolling();
            liveBet = null;

            if (!result.success) {
            displayResultEl.textContent = result.message || 'Something went wrong.';
            displayResultEl.classList.remove('win');
            displayResultEl.classList.add('lose');
            renderHistory();
            return;
            }

            applyRoll(result.dice);
            updateScoreAndHistory(result);
            showResult(formatPatternLabel(selected), result.generated, result.win, result.points);

            placeBetEl.style.display = 'none';
            playAgainEl.style.display = 'inline-flex';
        });

        resetScoreEl.addEventListener('click', async () => {
            const confirmed = window.confirm('Reset your score and history?');
            if (!confirmed) return;

            const result = await callApi({ action: 'reset' });
            if (!result.success) return;
            updateScoreAndHistory(result);
            liveBet = null;
            displayBetEl.textContent = '—';
            displayNumberEl.textContent = '—';
            displayResultEl.textContent = '—';
            displayResultEl.classList.remove('win', 'lose');
            placeBetEl.style.display = 'inline-flex';
            playAgainEl.style.display = 'none';
            renderHistory();
        });

        playAgainEl.addEventListener('click', () => {
            liveBet = null;
            displayBetEl.textContent = '—';
            displayNumberEl.textContent = '—';
            displayResultEl.textContent = '—';
            displayResultEl.classList.remove('win', 'lose');
            placeBetEl.style.display = 'inline-flex';
            playAgainEl.style.display = 'none';
            renderHistory();
        });

        // Render initial state from PHP session
        renderHistory();
        </script>
    </body>
    </html>
