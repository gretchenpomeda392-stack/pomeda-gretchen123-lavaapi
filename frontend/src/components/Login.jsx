import { useState } from "react";
import {
    Link,
    useNavigate
} from "react-router-dom";

import { login } from "../api";

function Login() {
    const navigate = useNavigate();

    const [username, setUsername] = useState("");
    const [password, setPassword] = useState("");

    const [message, setMessage] = useState("");
    const [error, setError] = useState("");
    const [loading, setLoading] = useState(false);

    const handleSubmit = async (e) => {
        e.preventDefault();

        setMessage("");
        setError("");
        setLoading(true);

        try {
            const data = await login(
                username,
                password
            );

            setMessage(
                data.message ||
                "Login successful!"
            );

            setTimeout(() => {
                navigate("/products");
            }, 800);

        } catch (error) {
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

                <h1>Login</h1>

                <p className="auth-subtitle">
                    Sign in to your account
                </p>

                <input
                    type="text"
                    placeholder="Username"
                    value={username}
                    onChange={(e) =>
                        setUsername(e.target.value)
                    }
                    required
                />

                <input
                    type="password"
                    placeholder="Password"
                    value={password}
                    onChange={(e) =>
                        setPassword(e.target.value)
                    }
                    required
                />

                {error && (
                    <p className="error">
                        {error}
                    </p>
                )}

                {message && (
                    <p className="success">
                        {message}
                    </p>
                )}

                <button
                    type="submit"
                    disabled={loading}
                >
                    {loading
                        ? "Logging in..."
                        : "Login"}
                </button>

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