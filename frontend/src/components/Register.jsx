import { useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import { register } from "../api";

function Register() {
    const navigate = useNavigate();

    const [username, setUsername] = useState("");
    const [email, setEmail] = useState("");
    const [password, setPassword] = useState("");
    const [role, setRole] = useState("user");

    const [message, setMessage] = useState("");
    const [error, setError] = useState("");
    const [loading, setLoading] = useState(false);

    const handleSubmit = async (e) => {
        e.preventDefault();

        setMessage("");
        setError("");
        setLoading(true);

        try {
            const data = await register(
                username,
                email,
                password,
                role
            );

            setMessage(
                data.message ||
                "Registration successful!"
            );

            setUsername("");
            setEmail("");
            setPassword("");
            setRole("user");

            setTimeout(() => {
                navigate("/login");
            }, 1000);

        } catch (error) {
            setError(
                error.message ||
                "Registration failed."
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

                <h1>Create Account</h1>

                <p className="auth-subtitle">
                    Register a new account
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
                    type="email"
                    placeholder="Email"
                    value={email}
                    onChange={(e) =>
                        setEmail(e.target.value)
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

                <select
                    value={role}
                    onChange={(e) =>
                        setRole(e.target.value)
                    }
                    required
                >
                    <option value="user">
                        User
                    </option>

                    <option value="admin">
                        Admin
                    </option>
                </select>

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
                        ? "Registering..."
                        : "Register"}
                </button>

                <p className="switch-text">
                    Already have an account?{" "}

                    <Link
                        to="/login"
                        className="link"
                    >
                        Login
                    </Link>
                </p>

            </form>

        </div>
    );
}

export default Register;