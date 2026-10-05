import { useState } from "react";
import {
    Link,
    useNavigate
} from "react-router-dom";

import { createProduct } from "../api";

function AddProduct() {
    const navigate = useNavigate();

    const [productName, setProductName] =
        useState("");

    const [description, setDescription] =
        useState("");

    const [price, setPrice] =
        useState("");

    const [quantity, setQuantity] =
        useState("");

    const [error, setError] =
        useState("");

    const [success, setSuccess] =
        useState("");

    const [loading, setLoading] =
        useState(false);

    const handleSubmit = async (e) => {
        e.preventDefault();

        setError("");
        setSuccess("");
        setLoading(true);

        try {

            const response = await createProduct({
                product_name: productName.trim(),
                description: description.trim(),
                price: Number(price),
                quantity: Number(quantity)
            });

            console.log(
                "CREATE PRODUCT RESPONSE:",
                response
            );

            setSuccess(
                response?.message ||
                "Product created successfully."
            );

            /*
             * Small delay so the success message
             * can be seen before returning to list.
             */

            setTimeout(() => {
                navigate("/products", {
                    replace: true,
                    state: {
                        refresh: true,
                        message:
                            response?.message ||
                            "Product created successfully."
                    }
                });
            }, 500);

        } catch (error) {

            console.error(
                "CREATE PRODUCT ERROR:",
                error
            );

            setError(
                error.message ||
                "Unable to create product."
            );

        } finally {

            setLoading(false);
        }
    };

    return (
        <div className="products-container">

            <div className="product-header">

                <div>
                    <h1>
                        Product Management
                    </h1>

                    <p>
                        Add a new product
                    </p>
                </div>

            </div>

            <div className="crud-card">

                <div className="form-header">

                    <div>
                        <h2>
                            Add Product
                        </h2>

                        <p>
                            Enter the product information below.
                        </p>
                    </div>

                </div>

                {error && (
                    <p className="error">
                        {error}
                    </p>
                )}

                {success && (
                    <p className="success">
                        {success}
                    </p>
                )}

                <form
                    className="product-form"
                    onSubmit={handleSubmit}
                >

                    <div className="form-group">

                        <label>
                            Product Name
                        </label>

                        <input
                            type="text"
                            placeholder="Enter product name"
                            value={productName}
                            onChange={(e) =>
                                setProductName(
                                    e.target.value
                                )
                            }
                            maxLength={100}
                            required
                        />

                    </div>

                    <div className="form-group">

                        <label>
                            Description
                        </label>

                        <textarea
                            placeholder="Enter product description"
                            value={description}
                            onChange={(e) =>
                                setDescription(
                                    e.target.value
                                )
                            }
                            rows="4"
                        />

                    </div>

                    <div className="form-row">

                        <div className="form-group">

                            <label>
                                Price
                            </label>

                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                placeholder="0.00"
                                value={price}
                                onChange={(e) =>
                                    setPrice(
                                        e.target.value
                                    )
                                }
                                required
                            />

                        </div>

                        <div className="form-group">

                            <label>
                                Quantity
                            </label>

                            <input
                                type="number"
                                min="0"
                                placeholder="0"
                                value={quantity}
                                onChange={(e) =>
                                    setQuantity(
                                        e.target.value
                                    )
                                }
                                required
                            />

                        </div>

                    </div>

                    <div className="form-actions">

                        <Link
                            to="/products"
                            className="back-button"
                        >
                            Back
                        </Link>

                        <button
                            type="submit"
                            className="add-button"
                            disabled={loading}
                        >
                            {loading
                                ? "Saving..."
                                : "Save Product"}
                        </button>

                    </div>

                </form>

            </div>

        </div>
    );
}

export default AddProduct;