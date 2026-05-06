// Point this to the folder containing your individual PHP files
const API_BASE = "http://localhost/Final/backend/api";

const Iconics = {
    async call(route, method = "POST", data = null) {
        try {
            // Split "property/list_all" into "property" and "list_all"
            const parts = route.split('/');
            const mainRoute = parts[0]; // "property" or "auth"
            const action = parts[1];    // "list_all", "stats", etc.

            // Construct URL: http://localhost/Final/backend/api/property.php?action=list_all
            let url = `${API_BASE}/${mainRoute}.php`;
            if (action) {
                url += `?action=${action}`;
            }

            const options = {
                method,
                credentials: "include", // Essential for PHP Session cookies
            };

            if (data && method !== "GET") {
                if (data instanceof FormData) {
                    // Do NOT set Content-Type — browser sets multipart/form-data with boundary automatically
                    options.body = data;
                } else {
                    options.headers = { "Content-Type": "application/json" };
                    options.body = JSON.stringify(data);
                }
            } else {
                // No body — still need to declare headers for JSON responses
                options.headers = { "Content-Type": "application/json" };
            }

            const res = await fetch(url, options);

            // Check if the response is actually JSON before parsing
            const contentType = res.headers.get("content-type");
            if (!contentType || !contentType.includes("application/json")) {
                const text = await res.text();
                console.error("Server returned non-JSON response:", text);
                return { status: "error", message: "Server error: Invalid response format" };
            }

            return await res.json();

        } catch (err) {
            console.error("API Error:", err);
            return { status: "error", message: "Network connection failed" };
        }
    },

    login(data) {
        return this.call("auth/login", "POST", data);
    },

    register(data) {
        return this.call("auth/register", "POST", data);
    },

    check() {
        return this.call("auth/check", "GET");
    },

    redirectByRole(role) {
        switch (role) {
            case 'admin':
                window.location.href = 'admin/dashboard.html';
                break;
            case 'agent':
                window.location.href = 'agent/dashboard.html';
                break;
            case 'client':
                window.location.href = 'client/dashboard.html';
                break;
            default:
                window.location.href = 'index.html';
        }
    }
};