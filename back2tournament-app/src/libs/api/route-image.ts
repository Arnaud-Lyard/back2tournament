import "server-only"

import { NextResponse } from "next/server"
import { MAX_IMAGE_BYTES } from "@/features/images/lib/image-file"

type ImageUpload = { image: File } | { refusal: NextResponse }

/**
 * Reads the `image` field of a multipart upload: the file to forward, or the
 * 400 to answer when there is none or it is over 8 MB, so that the backend
 * is not sent what it would refuse anyway. Its type is left to the backend,
 * which reads it from the content.
 */
export async function readImageUpload(request: Request): Promise<ImageUpload> {
  const form = await request.formData().catch(() => undefined)
  const image = form?.get("image")

  if (!(image instanceof File) || image.size === 0) {
    return {
      refusal: NextResponse.json({ message: "No image" }, { status: 400 }),
    }
  }
  if (image.size > MAX_IMAGE_BYTES) {
    return {
      refusal: NextResponse.json(
        { message: "The image weighs more than 8 MB" },
        { status: 400 }
      ),
    }
  }

  return { image }
}
