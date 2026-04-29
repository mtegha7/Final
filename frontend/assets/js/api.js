const API_BASE = "http://localhost/Final/backend/index.php";

const Iconics = {


    // CORE API CALL WRAPPER 
    async call(route, method = "POST", data = null) {
        try {
            const url = `${API_BASE}?route=${route}`;

            const res = await fetch(url, {
                method,
                headers: {
                    "Content-Type": "application/json"
                },
                credentials: "include",
                body: data ? JSON.stringify(data) : null
            });

            const json = await res.json();

            if (!res.ok) {
                console.error("API Error:", json);
            }

            return json;

        } catch (err) {
            console.error("Network Error:", err);
            return {
                success: false,
                message: "Network error"
            };
        }
    },


    // AUTH CHECK + ROLE GUARD 
    async checkAuth(requiredRole = null) {
        const res = await this.call("auth/check", "GET");

        if (!res || !res.success || !res.user) {
            window.location.href = "/Final/frontend/login.html";
            return null;
        }

        const user = res.user;

        // Role validation
        if (requiredRole && user.role !== requiredRole) {
            alert("Unauthorized Access");

            // smart redirect
            this.redirectByRole(user.role);
            return null;
        }

        return user;
    },


    // ROLE ROUTING
    redirectByRole(role) {
        if (role === "admin") {
            window.location.href = "/Final/frontend/admin/dashboard.html";
        } else if (role === "agent") {
            window.location.href = "/Final/frontend/agent/dashboard.html";
        } else if (role === "client") {
            window.location.href = "/Final/frontend/client/dashboard.html";
        } else {
            window.location.href = "/Final/frontend/login.html";
        }
    },


    // LOGOUT 
    async logout() {
        await this.call("auth/logout", "POST");
        window.location.href = "/Final/frontend/login.html";
    }
};