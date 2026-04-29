const API_BASE = "http://localhost/Final/backend/index.php";

const Iconics = {
    async call(route, method = "POST", data = null) {
        try {
            // Split "auth/login" into main route ("auth") and action ("login")
            const parts = route.split('/');
            const mainRoute = parts[0];
            const action = parts[1];

            // Build URL with parameters expected by your PHP files
            let url = `${API_BASE}?route=${mainRoute}`;
            if (action) {
                url += `&action=${action}`;
            }

            const options = {
                method,
                credentials: "include", // Required for Session cookies
                headers: {
                    "Content-Type": "application/json"
                }
            };

            if (data && method !== "GET") {
                options.body = JSON.stringify(data);
            }

            const res = await fetch(url, options);
            return await res.json();

        } catch (err) {
            console.error("API Error:", err);
            return { success: false, message: "Network connection failed" };
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
        console.log("Redirecting role:", role);

        // This path must match your folder name in htdocs exactly
        const BASE_URL = window.location.origin + "/Final/frontend/";

        let target = "";
        if (role === "admin") {
            target = "admin/dashboard.html";
        } else if (role === "agent") {
            target = "agent/dashboard.html";
        } else {
            target = "client/dashboard.html";
        }

        const destination = BASE_URL + target;
        console.log("Navigating to:", destination);

        // Force the redirect
        window.location.assign(destination);
    }
};