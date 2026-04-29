const Iconics = {

    base: "http://localhost/Final/backend/api/auth.php",

    async call(action, method = "POST", data = null) {

        let url = `${this.base}?action=${action}`;

        const options = {
            method,
            credentials: "include"
        };

        if (method === "POST" && data) {
            options.body = data;
        }

        const res = await fetch(url, options);
        return res.json();
    },

    login(formData) {
        return this.call("login", "POST", formData);
    },

    register(formData) {
        return this.call("register", "POST", formData);
    },

    check() {
        return this.call("check", "GET");
    },

    logout() {
        return fetch(`${this.base}?action=logout`, {
            credentials: "include"
        }).then(() => {
            window.location.href = "/login.html";
        });
    },

    redirectByRole(role) {
        if (role === "admin") window.location.href = "/admin/dashboard.html";
        if (role === "agent") window.location.href = "/agent/dashboard.html";
        if (role === "client") window.location.href = "/client/dashboard.html";
    }
};