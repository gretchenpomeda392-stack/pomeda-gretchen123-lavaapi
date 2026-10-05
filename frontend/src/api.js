import axios from "axios";


// ========================================
// LAVALUST API URL
// ========================================

// PALITAN ITO NG ACTUAL RENDER BACKEND URL
const API_URL = "https://pomeda-gretchen123-lavaapi.onrender.com";


// ========================================
// AXIOS INSTANCE
// ========================================

const api = axios.create({
    baseURL: API_URL,
    headers: {
        "Content-Type": "application/json",
        "Accept": "application/json"
    }
});


// ========================================
// ADD TOKEN AUTOMATICALLY
// ========================================

api.interceptors.request.use(
    (config) => {

        const token =
            localStorage.getItem("access_token");

        if (token) {
            config.headers.Authorization =
                `Bearer ${token}`;
        }

        return config;
    },

    (error) => {
        return Promise.reject(error);
    }
);


// ========================================
// ERROR HANDLER
// ========================================

function handleError(error) {

    console.error("API ERROR:", error);

    if (error.response) {

        console.error(
            "STATUS:",
            error.response.status
        );

        console.error(
            "DATA:",
            error.response.data
        );

        const data =
            error.response.data;

        throw new Error(
            data?.message ||
            data?.error ||
            `HTTP Error ${error.response.status}`
        );
    }

    if (error.request) {

        throw new Error(
            "Unable to connect to the API server."
        );
    }

    throw new Error(
        error.message ||
        "Something went wrong."
    );
}


// ========================================
// REGISTER
// POST /register
// ========================================

export async function register(
    username,
    email,
    password,
    role
) {

    try {

        const response =
            await api.post(
                "/register",
                {
                    username,
                    email,
                    password,
                    role
                }
            );

        return response.data;

    } catch (error) {

        handleError(error);
    }
}


// ========================================
// LOGIN
// POST /login
// ========================================

export async function login(
    username,
    password
) {

    try {

        const response =
            await api.post(
                "/login",
                {
                    username,
                    password
                }
            );

        const data =
            response.data;


        // ACCESS TOKEN
        if (
            data?.tokens?.access_token
        ) {

            localStorage.setItem(
                "access_token",
                data.tokens.access_token
            );
        }


        // REFRESH TOKEN
        if (
            data?.tokens?.refresh_token
        ) {

            localStorage.setItem(
                "refresh_token",
                data.tokens.refresh_token
            );
        }


        // USER
        if (data?.user) {

            localStorage.setItem(
                "user",
                JSON.stringify(data.user)
            );
        }


        return data;

    } catch (error) {

        handleError(error);
    }
}


// ========================================
// LOGOUT
// POST /logout
// ========================================

export async function logout() {

    const refreshToken =
        localStorage.getItem(
            "refresh_token"
        );

    try {

        await api.post(
            "/logout",
            {
                refresh_token:
                    refreshToken
            }
        );

    } catch (error) {

        console.error(
            "Logout error:",
            error
        );

    } finally {

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
}


// ========================================
// REFRESH TOKEN
// POST /refresh-token
// ========================================

export async function refreshToken() {

    const refresh_token =
        localStorage.getItem(
            "refresh_token"
        );

    try {

        const response =
            await api.post(
                "/refresh-token",
                {
                    refresh_token
                }
            );

        const data =
            response.data;


        if (
            data?.tokens?.access_token
        ) {

            localStorage.setItem(
                "access_token",
                data.tokens.access_token
            );
        }


        if (
            data?.tokens?.refresh_token
        ) {

            localStorage.setItem(
                "refresh_token",
                data.tokens.refresh_token
            );
        }


        return data;

    } catch (error) {

        handleError(error);
    }
}


// ========================================
// PROFILE
// GET /profile
// ========================================

export async function getProfile() {

    try {

        const response =
            await api.get(
                "/profile"
            );

        return response.data;

    } catch (error) {

        handleError(error);
    }
}


// ========================================
// PRODUCTS
// ========================================


// GET ALL
// GET /products

export async function getProducts() {

    try {

        const response =
            await api.get(
                "/products"
            );

        return response.data;

    } catch (error) {

        handleError(error);
    }
}


// GET ONE
// GET /products/:id

export async function getProduct(id) {

    try {

        const response =
            await api.get(
                `/products/${id}`
            );

        return response.data;

    } catch (error) {

        handleError(error);
    }
}


// CREATE
// POST /products

export async function createProduct(
    product
) {

    try {

        const response =
            await api.post(
                "/products",
                product
            );

        return response.data;

    } catch (error) {

        handleError(error);
    }
}


// UPDATE
// PUT /products/:id

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

        handleError(error);
    }
}


// DELETE
// DELETE /products/:id

export async function deleteProduct(
    id
) {

    try {

        const response =
            await api.delete(
                `/products/${id}`
            );

        return response.data;

    } catch (error) {

        handleError(error);
    }
}


export default api;