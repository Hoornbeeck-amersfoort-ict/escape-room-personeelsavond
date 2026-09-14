/**
 * Formats a whole number of seconds as a clock string. Used purely for
 * visual ticking between server updates — the server remains the source of
 * truth for every timer and countdown in the app.
 */
window.formatDuration = function (totalSeconds) {
    const seconds = Math.max(0, Math.floor(totalSeconds));
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;

    const pad = (value) => String(value).padStart(2, '0');

    if (hours > 0) {
        return `${pad(hours)}:${pad(minutes)}:${pad(secs)}`;
    }

    return `${pad(minutes)}:${pad(secs)}`;
};
