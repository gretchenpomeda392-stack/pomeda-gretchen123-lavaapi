import axios from "axios";

const api = axios.create({
    baseURL: import.meta.env.VITE_API_URL,
    headers: {
        "Content-Type": "application/json",
        "Accept": "application/json"
    }
});

// REGISTER
export async function register(
    username,
    email,
    password,
    role = "user"
) {
    try {
        const response = await api.post(
            "/register",
            {
                username,
                email,
                password,
                role
            }
        );

        console.log(
            "REGISTER RESPONSE:",
            response.data
        );

        return response.data;

    } catch (error) {

        console.error(
            "REGISTER ERROR:",
            error
        );

        if (error.response) {
            throw new Error(
                error.response.data?.message ||
                error.response.data?.error ||
                `HTTP Error ${error.response.status}`
            );
        }

        throw new Error(
            "Unable to connect to the API server."
        );
    }
}

// LOGIN
export async function login(
    username,
    password
) {
    try {
        const response = await api.post(
            "/login",
            {
                username,
                password
            }
        );

        console.log(
            "LOGIN RESPONSE:",
            response.data
        );

        const data = response.data;

        const accessToken =
            data?.access_token ||
            data?.tokens?.access_token ||
            data?.token ||
            null;

        const refreshToken =
            data?.refresh_token ||
            data?.tokens?.refresh_token ||
            null;

        if (accessToken) {
            localStorage.setItem(
                "access_token",
                accessToken
            );
        }

        if (refreshToken) {
            localStorage.setItem(
                "refresh_token",
                refreshToken
            );
        }

        if (data?.user) {
            localStorage.setItem(
                "user",
                JSON.stringify(data.user)
            );
        }

        return data;

    } catch (error) {

        console.error(
            "LOGIN ERROR:",
            error
        );

        if (error.response) {
            throw new Error(
                error.response.data?.message ||
                error.response.data?.error ||
                `HTTP Error ${error.response.status}`
            );
        }

        throw new Error(
            "Unable to connect to the API server."
        );
    }
}

// LOGOUT
export async function logout() {

    const refreshToken =
        localStorage.getItem(
            "refresh_token"
        );

    try {
        await api.post(
            "/logout",
            {
                refresh_token: refreshToken
            }
        );
    } catch (error) {
        console.error(
            "LOGOUT ERROR:",
            error
        );
    }

    localStorage.removeItem(
        "access_token"
    );

    localStorage.removeItem(
        "refresh_token"
    );

    localStorage.removeItem(
        "user"
    );
}

// GET PRODUCTS
export async function getProducts() {

    try {
        const response =
            await api.get("/products");

        return response.data;

    } catch (error) {

        console.error(
            "GET PRODUCTS ERROR:",
            error
        );

        throw error;
    }
}

// GET ONE PRODUCT
export async function getProduct(id) {

    try {
        const response =
            await api.get(
                `/products/${id}`
            );

        return response.data;

    } catch (error) {

        console.error(
            "GET PRODUCT ERROR:",
            error
        );

        throw error;
    }
}

// CREATE PRODUCT
export async function createProduct(product) {

    try {
        const response =
            await api.post(
                "/products",
                product
            );

        return response.data;

    } catch (error) {

        console.error(
            "CREATE PRODUCT ERROR:",
            error
        );

        throw error;
    }
}

// UPDATE PRODUCT
export async function updateProduct(
    id,
    product
) {

    try {
        const response =
            await api.put(
                `/products/${id}`,
                product
            );

        return response.data;

    } catch (error) {

        console.error(
            "UPDATE PRODUCT ERROR:",
            error
        );

        throw error;
    }
}

// DELETE PRODUCT
export async function deleteProduct(id) {

    try {
        const response =
            await api.delete(
                `/products/${id}`
            );

        return response.data;

    } catch (error) {

        console.error(
            "DELETE PRODUCT ERROR:",
            error
        );

        throw error;
    }
}

export default api;