import { z } from "zod"
import { message } from "@/libs/validation"

/**
 * Mirrors the backend's Email, Username and Password value objects, so a
 * refusal is explained field by field, in the user's language, before the
 * request ever leaves the browser.
 */
export const registerSchema = z
  .object({
    email: z
      .string()
      .trim()
      .pipe(z.email(message("invalidEmail"))),
    username: z
      .string()
      .trim()
      .min(3, message("usernameTooShort"))
      .max(30, message("usernameTooLong"))
      .regex(/^[A-Za-z0-9_-]+$/, message("usernameCharacters")),
    password: z
      .string()
      .min(8, message("passwordTooShort"))
      .regex(/\d/, message("passwordDigit"))
      .regex(/[A-Z]/, message("passwordUppercase"))
      .regex(/[a-z]/, message("passwordLowercase"))
      .regex(/\W/, message("passwordSpecial"))
      .refine((password) => !/\s/.test(password), message("passwordSpaces")),
    passwordConfirmation: z.string().min(1, message("required")),
  })
  .refine((data) => data.password === data.passwordConfirmation, {
    error: message("passwordsMismatch"),
    path: ["passwordConfirmation"],
  })

export type RegisterInput = z.infer<typeof registerSchema>
