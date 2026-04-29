async function protectPage(requiredRole = null) {

    const res = await Iconics.check();

    if (!res.success) {
        window.location.href = "/login.html";
        return null;
    }

    const user = res.user;

    if (requiredRole && user.role !== requiredRole) {
        window.location.href = "/login.html";
        return null;
    }

    return user;
}