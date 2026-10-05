import { useState } from "react";

import {
    Link,
    useNavigate
} from "react-router-dom";

import { login } from "../api";


function Login() {

    const navigate =
        useNavigate();


    const [username, setUsername] =
        useState("");

    const [password, setPassword] =
        useState("");

    const [message, setMessage] =
        useState("");

    const [error, setError] =
        useState("");

    const [loading, setLoading] =
        useState(false);


    // ========================================
    // HANDLE LOGIN
    // ========================================

    const handleSubmit =
        async (e) => {

            e.preventDefault();

            setMessage("");
            setError("");
            setLoading(true);


            try {

                const data =
                    await login(
                        username,
                        password
                    );


                console.log(
                    "LOGIN SUCCESS:",
                    data
                );


                // ====================================
                // CHECK ACCESS TOKEN
                // ====================================

                const token =
                    localStorage.getItem(
                        "access_token"
                    );


                if (!token) {

                    throw new Error(
                        "Login succeeded but access token was not saved."
                    );
                }


                console.log(
                    "TOKEN EXISTS:",
                    true
                );


                setMessage(
                    "Login successful! Redirecting..."
                );


                // ====================================
                // REDIRECT TO PRODUCTS
                // ====================================

                navigate(
                    "/products",
                    {
                        replace: true
                    }
                );

            } catch (error) {

                console.error(
                    "LOGIN ERROR:",
                    error
                );


                setError(
                    error.message ||
                    "Login failed."
                );

            } finally {

                setLoading(false);
            }
        };


    return (

        <div className="auth-container">

            <form
                className="auth-card"
                onSubmit={handleSubmit}
            >

                <h1>
                    Login
                </h1>


                <p className="auth-subtitle">
                    Sign in to your account
                </p>


                {/* USERNAME */}

                <input
                    type="text"
                    placeholder="Username"
                    value={username}
                    onChange={(e) =>
                        setUsername(
                            e.target.value
                        )
                    }
                    required
                />


                {/* PASSWORD */}

                <input
                    type="password"
                    placeholder="Password"
                    value={password}
                    onChange={(e) =>
                        setPassword(
                            e.target.value
                        )
                    }
                    required
                />


                {/* ERROR MESSAGE */}

                {error && (

                    <p className="error">
                        {error}
                    </p>

                )}


                {/* SUCCESS MESSAGE */}

                {message && (

                    <p className="success">
                        {message}
                    </p>

                )}


                {/* LOGIN BUTTON */}

                <button
                    type="submit"
                    disabled={loading}
                >

                    {loading
                        ? "Logging in..."
                        : "Login"
                    }

                </button>


                {/* REGISTER LINK */}

                <p className="switch-text">

                    Don't have an account?{" "}

                    <Link
                        to="/register"
                        className="link"
                    >
                        Register
                    </Link>

                </p>

            </form>

        </div>
    );
}


export default Login;